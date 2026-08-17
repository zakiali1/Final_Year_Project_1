<?php
header("Content-Type: application/json");
error_reporting(0); // Suppress native PHP errors from breaking JSON response

$requestData = json_decode(file_get_contents("php://input"));
$code = $requestData->code ?? '';

if (empty(trim($code))) {
    echo json_encode(["error" => "No code provided."]);
    exit;
}

// ==========================================
// PHASE 1: THE POLYGLOT LEXER
// ==========================================
function lexer($code) {
    $tokens = [];
    
    $patterns = [
        'COMMENT'    => '/^(\/\/|\#).*/',         // Handles both // (C++/Java) and # (Python)
        'STRING'     => '/^"[^"]*"|^\'[^\']*\'/',
        'NUMBER'     => '/^\d+(\.\d+)?/',
        'IDENTIFIER' => '/^[a-zA-Z_][a-zA-Z0-9_\.]*/', // Allows dots for System.out.println
        'L_SHIFT'    => '/^<</',                  // C++ cout operator
        'EQUALS'     => '/^=/',
        'SEMICOLON'  => '/^;/',                   // C++/Java line endings
        'POWER'      => '/^\*\*/',
        'MULTIPLY'   => '/^\*/',
        'DIVIDE'     => '/^\//',
        'PLUS'       => '/^\+/',
        'MINUS'      => '/^\-/',
        'L_PAREN'    => '/^\(/',
        'R_PAREN'    => '/^\)/',
        'SPACE'      => '/^\s+/'
    ];

    $offset = 0;
    $length = strlen($code);

    while ($offset < $length) {
        $matchFound = false;
        $substring = substr($code, $offset);

        foreach ($patterns as $type => $pattern) {
            if (preg_match($pattern, $substring, $matches)) {
                if ($type !== 'SPACE' && $type !== 'COMMENT') { 
                    $tokens[] = ['type' => $type, 'value' => $matches[0]];
                }
                $offset += strlen($matches[0]);
                $matchFound = true;
                break;
            }
        }
        if (!$matchFound) {
            throw new Exception("Syntax Error near '" . substr($substring, 0, 15) . "...'");
        }
    }
    return $tokens;
}

// ==========================================
// PHASE 2: THE POLYGLOT PARSER
// ==========================================
class Parser {
    private $tokens;
    private $pos = 0;

    public function __construct($tokens) { $this->tokens = $tokens; }
    private function current() { return $this->tokens[$this->pos] ?? null; }
    private function consume($type) {
        $token = $this->current();
        if ($token && $token['type'] === $type) {
            $this->pos++; return $token;
        }
        return null;
    }

    public function parse() {
        $statements = [];
        while ($this->pos < count($this->tokens)) {
            $statements[] = $this->statement();
            $this->consume('SEMICOLON'); // Gracefully eat trailing semicolons if they exist
        }
        return $statements;
    }

    private function statement() {
        $startPos = $this->pos;
        $token = $this->current();
        
        // 1. Skip type declarations (int, double, float, var, String) 
        if ($token && in_array($token['value'], ['int', 'double', 'float', 'String', 'var'])) {
            $this->consume('IDENTIFIER'); 
        }

        $id = $this->consume('IDENTIFIER');
        
        if ($id) {
            // A. Handle Variable Assignment (e.g., a = 5)
            if ($this->consume('EQUALS')) {
                $expr = $this->expression();
                return ['type' => 'Assignment', 'name' => $id['value'], 'value' => $expr];
            }
            
            // B. Handle Python/Java Print: print(x) OR System.out.println(x)
            if (($id['value'] === 'print' || $id['value'] === 'System.out.println') && $this->consume('L_PAREN')) {
                $expr = $this->expression();
                if (!$this->consume('R_PAREN')) throw new Exception("Syntax Error: Missing ')' after print block.");
                return ['type' => 'Print', 'value' => $expr];
            }

            // C. Handle C++ Print: cout << x << endl;
            if ($id['value'] === 'cout') {
                $exprs = [];
                while ($this->consume('L_SHIFT')) {
                    $next = $this->current();
                    // Ignore 'endl' command but process it safely
                    if ($next && $next['value'] === 'endl') {
                        $this->consume('IDENTIFIER');
                    } else {
                        $exprs[] = $this->expression();
                    }
                }
                return ['type' => 'PrintCout', 'values' => $exprs];
            }
        }
        
        $this->pos = $startPos;
        return $this->expression();
    }

    private function expression() {
        $left = $this->term();
        while ($op = ($this->consume('PLUS') ?: $this->consume('MINUS'))) {
            $left = ['type' => 'BinaryOperation', 'operator' => $op['type'], 'left' => $left, 'right' => $this->term()];
        }
        return $left;
    }

    private function term() {
        $left = $this->power();
        while ($op = ($this->consume('MULTIPLY') ?: $this->consume('DIVIDE'))) {
            $left = ['type' => 'BinaryOperation', 'operator' => $op['type'], 'left' => $left, 'right' => $this->power()];
        }
        return $left;
    }

    private function power() {
        $left = $this->primary();
        if ($op = $this->consume('POWER')) {
            $left = ['type' => 'BinaryOperation', 'operator' => $op['type'], 'left' => $left, 'right' => $this->power()];
        }
        return $left;
    }

    private function primary() {
        if ($num = $this->consume('NUMBER')) return ['type' => 'Number', 'value' => (float)$num['value']];
        if ($id = $this->consume('IDENTIFIER')) return ['type' => 'Identifier', 'name' => $id['value']];
        if ($str = $this->consume('STRING')) return ['type' => 'String', 'value' => substr($str['value'], 1, -1)];
        
        if ($this->consume('L_PAREN')) {
            $expr = $this->expression();
            if (!$this->consume('R_PAREN')) throw new Exception("Syntax Error: Unclosed parenthesis.");
            return $expr;
        }
        if ($this->consume('MINUS')) return ['type' => 'UnaryOperation', 'operator' => 'MINUS', 'operand' => $this->primary()];
        
        throw new Exception("Unexpected token near execution block. Syntax verification failed.");
    }
}

// ==========================================
// PHASE 3: THE EVALUATOR
// ==========================================
class Evaluator {
    public $env = []; 
    public $outputBuffer = [];

    public function evaluateProgram($statements) {
        foreach ($statements as $stmt) { $this->evaluate($stmt); }
        if (empty($this->outputBuffer)) return "(No output.)";
        return implode("\n", $this->outputBuffer);
    }

    private function evaluate($node) {
        if (!isset($node['type'])) return null;

        if ($node['type'] === 'Number' || $node['type'] === 'String') return $node['value'];
        
        if ($node['type'] === 'Identifier') {
            if (isset($this->env[$node['name']])) return $this->env[$node['name']];
            throw new Exception("NameError: variable '" . $node['name'] . "' is not defined");
        }
        
        if ($node['type'] === 'Assignment') {
            $val = $this->evaluate($node['value']);
            $this->env[$node['name']] = $val; 
            return $val;
        }

        if ($node['type'] === 'Print') {
            $val = $this->evaluate($node['value']);
            $this->outputBuffer[] = $val;
            return $val;
        }

        if ($node['type'] === 'PrintCout') {
            $out = '';
            foreach ($node['values'] as $v) { $out .= $this->evaluate($v); }
            $this->outputBuffer[] = $out;
            return $out;
        }
        
        if ($node['type'] === 'UnaryOperation') {
            $val = $this->evaluate($node['operand']);
            if ($node['operator'] === 'MINUS') return -$val;
        }
        
        if ($node['type'] === 'BinaryOperation') {
            $left = $this->evaluate($node['left']); 
            $right = $this->evaluate($node['right']);
            switch ($node['operator']) {
                case 'PLUS': 
                    // Safely handle string concatenation
                    if (is_string($left) || is_string($right)) {
                        return (string)$left . (string)$right;
                    }
                    return $left + $right;
                case 'MINUS': return $left - $right;
                case 'MULTIPLY': return $left * $right;
                case 'DIVIDE': 
                    if ($right == 0) throw new Exception("ZeroDivisionError: division by zero");
                    return $left / $right;
                case 'POWER': return pow($left, $right);
            }
        }
        return null;
    }
}

// ==========================================
// RUN THE COMPILER
// ==========================================
try {
    $tokens = lexer($code);
    $parser = new Parser($tokens);
    $ast = $parser->parse();
    
    $evaluator = new Evaluator();
    $finalOutput = $evaluator->evaluateProgram($ast);
    
    echo json_encode(["output" => $finalOutput]);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>