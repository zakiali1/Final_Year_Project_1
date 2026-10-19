# PyCharm.micro Polyglot IDE

PyCharm.micro is a browser-based code editor and learning project for programming practice. It provides a PyCharm-inspired interface and a PHP backend that tokenizes, parses, and evaluates a small subset of Python-, C++-, and Java-style code.

## Features

- Edit and run sample code in the browser.
- Switch between Python, C++, and Java example templates.
- Save the current code as a local file.
- View program output and basic syntax or runtime errors in the run terminal.
- Evaluate variable assignments, arithmetic expressions, and basic print statements.

## Requirements

- PHP 7.0 or newer
- A modern web browser

No Composer packages or database are required.

## Run locally

From the project directory, start PHP's built-in web server:

```sh
php -S localhost:8000
```

Open [http://localhost:8000](http://localhost:8000) in your browser. The editor sends code to `compiler.php` as JSON, so keep the PHP server running while using the app.

## Supported code

The backend is a small educational interpreter, not a full Python, C++, or Java compiler. It supports numeric and string literals, variable assignments, parentheses, unary minus, `+`, `-`, `*`, `/`, `**`, and basic output using `print(...)`, `System.out.println(...)`, or `cout << ...`.

For example, this Python-style snippet runs in the editor:

```python
a = 10
b = 5
print(a + b)
```

The language selector changes the sample code and saved file extension. The C++ and Java templates use the interpreter's supported subset; native compiler features, libraries, and general language syntax are not available.
