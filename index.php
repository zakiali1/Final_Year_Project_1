<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>main.py - PyCharm Micro-Edition</title>
    <style>
        /* PyCharm Darcula Palette colors */
        :root {
            --bg-dark: #2b2b2b;
            --panel-bg: #3c3f41;
            --border-color: #323232;
            --text-color: #a9b7c6;
            --text-light: #ffffff;
            --ide-green: #59a869;
            --sidebar-width: 220px;
        }

        body, html {
            margin: 0; padding: 0; height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-dark); color: var(--text-color);
            overflow: hidden; user-select: none;
        }

        /* SPLASH SCREEN STYLE */
        #splash-screen {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background-color: #1e1e1e; z-index: 9999;
            display: flex; justify-content: center; align-items: center;
            transition: opacity 0.5s ease-in-out;
        }
        .splash-card {
            width: 600px; height: 350px;
            background: linear-gradient(135deg, #2b2b2b 0%, #111111 100%);
            border: 1px solid #444; box-shadow: 0 10px 30px rgba(0,0,0,0.7);
            position: relative; padding: 40px; box-sizing: border-box;
            display: flex; flex-direction: column; justify-content: space-between;
        }
        .splash-branding h1 { margin: 0; font-size: 42px; color: #fff; font-weight: 800; letter-spacing: -1px; }
        .splash-branding h1 span { color: var(--ide-green); }
        .splash-branding p { margin: 5px 0 0 0; color: #888; font-size: 14px; text-transform: uppercase; letter-spacing: 2px; }
        
        .developer-details { margin-top: 20px; border-left: 3px solid var(--ide-green); padding-left: 15px; }
        .developer-details h3 { margin: 0 0 5px 0; color: #eee; font-size: 18px; }
        .developer-details p { margin: 0 0 3px 0; color: #aaa; font-size: 14px; }
        
        .splash-footer { font-size: 12px; color: #555; }
        .loading-bar-container { width: 100%; height: 4px; background-color: #333; position: absolute; bottom: 0; left: 0; }
        .loading-bar {
            width: 0%; height: 100%; background-color: var(--ide-green);
            animation: loadProgress 25s linear forwards;
        }
        @keyframes loadProgress { 0% { width: 0%; } 100% { width: 100%; } }

        /* PYCHARM MAIN WINDOW INTERFACE */
        .ide-container { display: flex; flex-direction: column; height: 100vh; width: 100vw; }
        
        /* UPDATED MENU BAR & DROPDOWNS */
        .menu-bar { background-color: var(--panel-bg); height: 30px; display: flex; align-items: center; border-bottom: 1px solid var(--border-color); font-size: 13px; }
        .menu-item { position: relative; padding: 0 12px; line-height: 30px; cursor: pointer; color: #bbb; }
        .menu-item:hover { background-color: #4b4d4f; color: #fff; }
        
        .dropdown { display: none; position: absolute; top: 100%; left: 0; background-color: #3c3f41; border: 1px solid var(--border-color); box-shadow: 0 4px 6px rgba(0,0,0,0.3); z-index: 1000; min-width: 180px; }
        .menu-item:hover .dropdown { display: block; }
        .dropdown-item { padding: 0 15px; line-height: 32px; color: #bbb; display: block; text-decoration: none; }
        .dropdown-item:hover { background-color: #2d4765; color: #fff; }
        .dropdown-divider { height: 1px; background-color: var(--border-color); margin: 4px 0; }
        
        .toolbar { background-color: var(--panel-bg); height: 40px; display: flex; align-items: center; justify-content: space-between; padding: 0 15px; border-bottom: 1px solid var(--border-color); }
        .toolbar-left { font-size: 13px; color: #bbb; display: flex; align-items: center; gap: 15px;}
        
        select {
            background-color: #2b2b2b; color: #a9b7c6; border: 1px solid #555;
            padding: 4px 8px; border-radius: 3px; font-family: inherit; font-size: 13px; outline: none; cursor: pointer;
        }
        
        .run-btn {
            background-color: #36583b; color: #fff; border: 1px solid #436a49;
            padding: 4px 12px; font-size: 12px; cursor: pointer; border-radius: 3px;
            display: flex; align-items: center; gap: 6px; font-weight: bold;
        }
        .run-btn:hover { background-color: var(--ide-green); }
        .run-icon { width: 0; height: 0; border-top: 5px solid transparent; border-bottom: 5px solid transparent; border-left: 8px solid #fff; }

        .workspace { display: flex; flex: 1; overflow: hidden; }
        .sidebar { width: var(--sidebar-width); background-color: var(--panel-bg); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; font-size: 13px; }
        .panel-header { padding: 6px 10px; background-color: #313335; font-weight: bold; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid var(--border-color); }
        .file-tree { padding: 10px; color: #bbb; }
        .tree-item { padding: 4px 0 4px 15px; cursor: pointer; }
        .tree-item.active { background-color: #2d4765; color: #fff; }

        .main-editor-section { flex: 1; display: flex; flex-direction: column; background-color: var(--bg-dark); }
        .editor-tabs { background-color: #3c3f41; height: 28px; display: flex; border-bottom: 1px solid var(--border-color); }
        .tab { background-color: var(--bg-dark); padding: 0 20px; display: flex; align-items: center; font-size: 12px; color: var(--text-light); border-right: 1px solid var(--border-color); }
        
        .editor-container { flex: 1; display: flex; position: relative; }
        .line-numbers { width: 40px; background-color: var(--bg-dark); border-right: 1px solid #3c3f41; padding-top: 10px; text-align: right; padding-right: 8px; font-family: 'Consolas', monospace; font-size: 15px; color: #555; line-height: 24px; }
        textarea { flex: 1; background-color: var(--bg-dark); border: none; color: #a9b7c6; font-family: 'Consolas', 'Courier New', monospace; font-size: 15px; padding: 10px; resize: none; outline: none; line-height: 24px; }

        .bottom-panel { height: 220px; background-color: var(--bg-dark); border-top: 1px solid var(--border-color); display: flex; flex-direction: column; }
        .console-output { flex: 1; background-color: #2b2b2b; color: #ccc; padding: 12px; font-family: 'Consolas', monospace; font-size: 13px; overflow-y: auto; white-space: pre-wrap; border-top: 1px solid var(--border-color); }

        /* MODALS */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 2000; justify-content: center; align-items: center; }
        .modal { background: #2b2b2b; border: 1px solid #444; border-radius: 5px; width: 420px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .modal-header { background: #3c3f41; padding: 12px 15px; font-size: 14px; font-weight: bold; border-bottom: 1px solid #444; border-radius: 5px 5px 0 0; color: #fff; }
        .modal-close { float: right; cursor: pointer; color: #888; font-size: 18px; line-height: 14px; }
        .modal-close:hover { color: #fff; }
        .modal-content { padding: 25px; color: #ccc; font-size: 14px; line-height: 1.6; }
    </style>
</head>
<body>

    <div id="splash-screen">
        <div class="splash-card">
            <div class="splash-branding">
                <h1>PyCharm<span>.micro</span></h1>
                <p>Polyglot IDE Compiler Platform</p>
                <div class="developer-details">
                    <h3>Fatima Zakiyya Ali</h3>
                    <p>NEU/22/23/IFT/00234</p>
                    <p>NorthEastern University Gombe</p>
                    <p style="color: #666; font-size: 12px; margin-top: 4px;">DESIGN AND IMPLEMENTATION OF AN ONLINE CODE COMPILER AND JUDGE SYSTEM FOR PROGRAMMING PRACTICE.</p>
                </div>
            </div>
            <div class="splash-footer">Loading C++, Java, and Python frameworks...</div>
            <div class="loading-bar-container"><div class="loading-bar"></div></div>
        </div>
    </div>

    <div id="modal-dev" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                About Developer
                <span class="modal-close" onclick="closeModal('modal-dev')">&times;</span>
            </div>
            <div class="modal-content">
                <h3 style="color: var(--ide-green); margin: 0 0 10px 0;">Fatima Zakiyya Ali</h3>
                <p style="margin: 5px 0;"><strong>Student ID:</strong> NEU/22/23/IFT/00234</p>
                <p style="margin: 5px 0;"><strong>Institution:</strong> NorthEastern University Gombe</p>
                <p style="color: #888; font-size: 0.9em; margin-top: 15px;">System Compiler Architecture Project</p>
            </div>
        </div>
    </div>

    <div id="modal-soft" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                About Software
                <span class="modal-close" onclick="closeModal('modal-soft')">&times;</span>
            </div>
            <div class="modal-content">
                <p style="font-weight: bold; margin: 0 0 10px 0; font-size: 16px; color: #fff;">PyCharm.micro Polyglot IDE</p>
                <p><strong>Project Topic:</strong> DESIGN AND IMPLEMENTATION OF AN ONLINE CODE COMPILER AND JUDGE SYSTEM FOR PROGRAMMING PRACTICE.</p>
                <p style="color: #666; margin-top: 20px; font-size: 0.9em;">Version 1.0.0 | Polyglot Compiler Architecture Engine</p>
            </div>
        </div>
    </div>

    <div class="ide-container">
        <div class="menu-bar">
            <div class="menu-item">File
                <div class="dropdown">
                    <div class="dropdown-item" id="menu-save">Save Code</div>
                    <div class="dropdown-item" id="menu-save-as">Save As...</div>
                    <div class="dropdown-divider"></div>
                    <div class="dropdown-item" id="menu-exit">Exit Editor</div>
                </div>
            </div>
            <div class="menu-item">Edit</div>
            <div class="menu-item">View</div>
            <div class="menu-item">Run
                <div class="dropdown">
                    <div class="dropdown-item" id="menu-run">Run Compilation</div>
                    <div class="dropdown-item" id="menu-stop">Stop Running</div>
                </div>
            </div>
            <div class="menu-item">Help
                <div class="dropdown">
                    <div class="dropdown-item" id="menu-about-dev">About Developer</div>
                    <div class="dropdown-item" id="menu-about-soft">About Software</div>
                </div>
            </div>
        </div>

        <div class="toolbar">
            <div class="toolbar-left">
                <span>MicroProject &gt; </span>
                <select id="languageSelect">
                    <option value="python">Python 3.9</option>
                    <option value="cpp">C++ (GCC)</option>
                    <option value="java">Java 17</option>
                </select>
                <span id="activeFile">📄 main.py</span>
            </div>
            <button class="run-btn" id="runBtn"><div class="run-icon"></div> Run Build</button>
        </div>

        <div class="workspace">
            <div class="sidebar">
                <div class="panel-header">Project Explorer</div>
                <div class="file-tree">
                    📁 <b>MicroProject</b>
                    <div class="tree-item active" id="treeFile">📄 main.py</div>
                    <div class="tree-item" style="color: #666;">⚙️ compiler.php</div>
                </div>
            </div>

            <div class="main-editor-section">
                <div class="editor-tabs"><div class="tab" id="tabFile">main.py</div></div>
                <div class="editor-container">
                    <div class="line-numbers">1<br>2<br>3<br>4<br>5<br>6<br>7<br>8<br>9<br>10</div>
                    <textarea id="sourceCode" spellcheck="false"># Python Test Suite
a = 10
b = 5
c = a + b

print("Execution analysis successful.")
print("The calculated value is:")
print(c)</textarea>
                </div>
            </div>
        </div>

        <div class="bottom-panel">
            <div class="panel-header" style="background-color: #3c3f41;"><span>Run Terminal</span></div>
            <div id="output" class="console-output">Process finished. Awaiting input execution...</div>
        </div>
    </div>

    <script>
        // BOOTSTRAP SPLASH
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const splash = document.getElementById('splash-screen');
                splash.style.opacity = '0';
                setTimeout(() => splash.style.display = 'none', 500);
            }, 25000); 
        });

        // MODAL LOGIC
        function openModal(id) { document.getElementById(id).style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
        
        document.getElementById('menu-about-dev').addEventListener('click', () => openModal('modal-dev'));
        document.getElementById('menu-about-soft').addEventListener('click', () => openModal('modal-soft'));

        // FILE MENU LOGIC
        function downloadFile(content, fileName) {
            const a = document.createElement("a");
            const file = new Blob([content], { type: "text/plain" });
            a.href = URL.createObjectURL(file);
            a.download = fileName;
            a.click();
            URL.revokeObjectURL(a.href);
        }

        document.getElementById('menu-save').addEventListener('click', () => {
            downloadFile(sourceCode.value, templates[langSelect.value].ext);
        });

        document.getElementById('menu-save-as').addEventListener('click', () => {
            const name = prompt("Enter filename to save as:", templates[langSelect.value].ext);
            if(name) downloadFile(sourceCode.value, name);
        });

        document.getElementById('menu-exit').addEventListener('click', () => {
            if(confirm("Are you sure you want to close the editor? Unsaved changes may be lost.")) {
                window.close(); // May be blocked by browser if not spawned by script, but it is standard
            }
        });

        // LANGUAGE TEMPLATES
        const templates = {
            python: {
                ext: "main.py",
                cmd: "python.exe main.py",
                code: `# Python Test Suite\na = 10\nb = 5\nc = a + b\n\nprint("Execution analysis successful.")\nprint("The calculated value is:")\nprint(c)`
            },
            cpp: {
                ext: "main.cpp",
                cmd: "g++ main.cpp -o main.exe && main.exe",
                code: `// C++ Test Suite\nint a = 10;\nint b = 5;\nint c = a + b;\n\ncout << "Execution analysis successful." << endl;\ncout << "The calculated value is:" << endl;\ncout << c << endl;`
            },
            java: {
                ext: "Main.java",
                cmd: "javac Main.java && java Main",
                code: `// Java Test Suite\nint a = 10;\nint b = 5;\nint c = a + b;\n\nSystem.out.println("Execution analysis successful.");\nSystem.out.println("The calculated value is:");\nSystem.out.println(c);`
            }
        };

        // SWITCH LANGUAGE LOGIC
        const langSelect = document.getElementById('languageSelect');
        const sourceCode = document.getElementById('sourceCode');
        
        langSelect.addEventListener('change', function() {
            const lang = this.value;
            document.getElementById('activeFile').innerText = "📄 " + templates[lang].ext;
            document.getElementById('treeFile').innerText = "📄 " + templates[lang].ext;
            document.getElementById('tabFile').innerText = templates[lang].ext;
            sourceCode.value = templates[lang].code;
        });

        // COMPILER EXECUTION LOGIC (WITH ABORT CONTROLLER)
        let activeRequest = null;

        function runCompilation() {
            const code = sourceCode.value;
            const outputDiv = document.getElementById('output');
            const lang = langSelect.value;

            outputDiv.style.color = "#bbbbbb";
            outputDiv.innerText = `C:\\Users\\Fatima\\MicroProject> ${templates[lang].cmd}\nCompiling and running instructions...\n`;

            if (activeRequest) { activeRequest.abort(); }
            activeRequest = new AbortController();

            fetch('compiler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: code, language: lang }),
                signal: activeRequest.signal
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    outputDiv.style.color = "#ff6b68";
                    outputDiv.innerText += "\nProcess terminated with an error:\n" + data.error;
                } else {
                    outputDiv.style.color = "#a9b7c6";
                    outputDiv.innerText += "\n" + data.output + "\n\nProcess finished with exit code 0";
                }
                activeRequest = null;
            })
            .catch(error => {
                if (error.name === 'AbortError') {
                    outputDiv.style.color = "#e6d76a";
                    outputDiv.innerText += "\nProcess manually terminated by user.";
                } else {
                    outputDiv.style.color = "#ff6b68";
                    outputDiv.innerText += "\nInternal IDE Connection Failure: " + error;
                }
                activeRequest = null;
            });
        }

        // Run/Stop listeners
        document.getElementById('runBtn').addEventListener('click', runCompilation);
        document.getElementById('menu-run').addEventListener('click', runCompilation);
        
        document.getElementById('menu-stop').addEventListener('click', () => {
            if (activeRequest) {
                activeRequest.abort();
                activeRequest = null;
            }
        });
    </script>
</body>
</html>