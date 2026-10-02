<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Issue Book - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsQR/1.4.0/jsQR.min.js"></script>
</head>
<body>
<div class="container mt-5 text-center">
    <h2 class="mb-4">📷 Issue Book via QR / Code</h2>
    <a href="index.php" class="btn btn-secondary mb-4">← Back to Home</a>

    <!-- Mode selector -->
    <div class="mb-4">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary mode-btn" data-mode="camera">📷 Camera</button>
            <button type="button" class="btn btn-outline-primary mode-btn" data-mode="upload">🖼️ Upload Image</button>
            <button type="button" class="btn btn-outline-primary mode-btn" data-mode="manual">⌨️ Enter Code</button>
        </div>
    </div>

    <!-- Camera -->
    <div id="sec-camera">
        <div id="reader" style="width:400px; max-width:100%; margin: 0 auto;"></div>
        <button id="camStartBtn" type="button" class="btn btn-success mt-2" onclick="startCamera()">▶ Start Camera</button>
        <button id="camStopBtn" type="button" class="btn btn-outline-danger mt-2" style="display:none;" onclick="stopCamera()">■ Stop Camera</button>
    </div>

    <!-- Upload -->
    <div id="sec-upload" style="display:none;">
        <div style="max-width: 350px; margin: 0 auto;" class="text-start">
            <label class="form-label">QR ki image chuno (photo ya screenshot)</label>
            <input type="file" id="qrFile" accept="image/*" class="form-control">
            <div class="form-text">Tip: image mein QR saaf dikhna chahiye. Poore page ka chhota screenshot ki jagah QR ke aas-paas thodi safed jagah chhod kar crop karo.</div>
        </div>
    </div>

    <!-- Manual code -->
    <div id="sec-manual" style="display:none;">
        <div style="max-width: 350px; margin: 0 auto;" class="text-start">
            <label class="form-label">Copy ID (QR ke neeche "Copy ID: ..." likha hota hai)</label>
            <div class="input-group">
                <input type="text" id="manualCode" class="form-control" placeholder="Copy ID likho">
                <button type="button" class="btn btn-primary" onclick="useManualCode()">Use</button>
            </div>
        </div>
    </div>

    <div id="fileReader" style="display:none;"></div>
    <div id="status" class="mt-3" style="display:none; max-width: 500px; margin-left:auto; margin-right:auto;"></div>

    <!-- Issue form -->
    <div id="scannedInfo" class="mt-4" style="display:none;">
        <div class="alert alert-info">
            Copy ID: <strong id="copyIdDisplay"></strong>
        </div>
        <div style="max-width: 350px; margin: 0 auto;" class="text-start">
            <label class="form-label">Student ERP ID</label>
            <input type="text" id="erpInput" class="form-control mb-3" placeholder="e.g. ERP1001">

            <label class="form-label">Issue Date</label>
            <input type="date" id="issueDateInput" class="form-control mb-3" required>

            <label class="form-label">Due Date</label>
            <input type="date" id="dueDateInput" class="form-control mb-3" required>

            <button id="issueBtn" type="button" onclick="issueBook()" class="btn btn-success w-100">Issue This Copy</button>
        </div>
    </div>

    <p id="result" class="fs-5 fw-bold mt-3"></p>

    <button onclick="location.reload()" class="btn btn-outline-secondary mt-3">🔄 Reset</button>
</div>

<script>
    let scannedCopyId = "";
    let camScanner = null;
    let camRunning = false;

    // ---------- helpers ----------
    // Local date (UTC nahi) taaki India mein raat/subah ko date galat na aaye
    function localToday() {
        const d = new Date();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return d.getFullYear() + "-" + mm + "-" + dd;
    }

    function setStatus(msg, type) {
        const el = document.getElementById("status");
        if (!msg) { el.style.display = "none"; el.innerText = ""; return; }
        el.className = "mt-3 alert alert-" + (type || "secondary");
        el.style.display = "";
        el.innerText = msg;
    }

    // Teeno tareeke (camera / image / manual) yahin aakar milte hain
    function setCopyId(code) {
        scannedCopyId = String(code).trim();
        if (!scannedCopyId) return;
        document.getElementById("copyIdDisplay").innerText = scannedCopyId;
        document.getElementById("scannedInfo").style.display = "block";

        const issueInput = document.getElementById("issueDateInput");
        if (!issueInput.value) issueInput.value = localToday();
        // Due Date jaan-boojh kar blank: admin khud select karega

        document.getElementById("result").innerText = "";
        document.getElementById("issueBtn").disabled = false;
        document.getElementById("erpInput").focus();
    }

    // ---------- mode switching ----------
    function setMode(mode) {
        if (mode !== "camera") stopCamera();
        ["camera", "upload", "manual"].forEach(m => {
            document.getElementById("sec-" + m).style.display = (m === mode) ? "" : "none";
        });
        document.querySelectorAll(".mode-btn").forEach(b => {
            const active = b.dataset.mode === mode;
            b.classList.toggle("btn-primary", active);
            b.classList.toggle("btn-outline-primary", !active);
        });
        setStatus("");
    }
    document.querySelectorAll(".mode-btn").forEach(b => {
        b.addEventListener("click", () => setMode(b.dataset.mode));
    });

    // ---------- camera ----------
    async function startCamera() {
        if (!window.isSecureContext) {
            setStatus("Camera ke liye HTTPS ya localhost zaroori hai. 'Upload Image' ya 'Enter Code' use karo.", "warning");
            return;
        }
        setStatus("");
        camRunning = true;
        try {
            camScanner = new Html5Qrcode("reader");
            await camScanner.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: 250 },
                (text) => {
                    if (!camRunning) return;
                    stopCamera().then(() => setCopyId(text));
                },
                () => {}
            );
            document.getElementById("camStartBtn").style.display = "none";
            document.getElementById("camStopBtn").style.display = "";
        } catch (e) {
            camRunning = false;
            setStatus("Camera start nahi hua: " + e + ". 'Upload Image' ya 'Enter Code' use karo.", "warning");
        }
    }

    async function stopCamera() {
        if (!camRunning) return;
        camRunning = false;
        try { await camScanner.stop(); } catch (e) {}
        try { camScanner.clear(); } catch (e) {}
        document.getElementById("camStartBtn").style.display = "";
        document.getElementById("camStopBtn").style.display = "none";
    }

    // ---------- image upload ----------
    function loadImage(file) {
        return new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => { URL.revokeObjectURL(url); resolve(img); };
            img.onerror = () => { URL.revokeObjectURL(url); reject(new Error("Image load nahi hui")); };
            img.src = url;
        });
    }

    // Fallback: image ko alag-alag size par, safed border ke saath jsQR se try karo
    async function decodeWithJsQR(file) {
        if (typeof jsQR !== "function") return null;
        const img = await loadImage(file);
        const srcW = img.naturalWidth, srcH = img.naturalHeight;
        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d", { willReadFrequently: true });

        const maxDims = [1000, 600, 1600, 400];
        const padRatios = [0, 0.12]; // 0 = jaisa hai, 0.12 = safed border jodkar

        for (const maxDim of maxDims) {
            const scale = maxDim / Math.max(srcW, srcH);
            const w = Math.max(1, Math.round(srcW * scale));
            const h = Math.max(1, Math.round(srcH * scale));

            for (const pr of padRatios) {
                const pad = Math.round(Math.max(w, h) * pr);
                canvas.width = w + pad * 2;
                canvas.height = h + pad * 2;
                ctx.imageSmoothingEnabled = scale < 1; // chhoti image bade karte waqt edges saaf rakho
                ctx.fillStyle = "#ffffff";
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, pad, pad, w, h);

                const data = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const res = jsQR(data.data, data.width, data.height, { inversionAttempts: "attemptBoth" });
                if (res && res.data) return res.data;

                await new Promise(r => setTimeout(r, 0)); // UI ko saans lene do
            }
        }
        return null;
    }

    async function decodeImageFile(file) {
        // 1) Pehle html5-qrcode
        try {
            const h = new Html5Qrcode("fileReader");
            const text = await h.scanFile(file, false);
            if (text) return text;
        } catch (e) { /* fallback par jao */ }

        // 2) Fallback: jsQR
        try {
            return await decodeWithJsQR(file);
        } catch (e) {
            return null;
        }
    }

    document.getElementById("qrFile").addEventListener("change", async function () {
        const file = this.files[0];
        if (!file) return;
        setStatus("Image padh raha hoon...", "info");

        const code = await decodeImageFile(file);
        if (code) {
            setStatus("QR mil gaya ✔", "success");
            setCopyId(code);
        } else {
            setStatus("Is image mein QR detect nahi hua. QR ko clear, bada aur thodi safed jagah ke saath crop karke dobara upload karo, ya 'Enter Code' se Copy ID likh do.", "warning");
        }
        this.value = ""; // same file dobara chunna ho toh chalega
    });

    // ---------- manual code ----------
    function useManualCode() {
        const v = document.getElementById("manualCode").value.trim();
        if (!v) {
            setStatus("Pehle Copy ID likho.", "warning");
            return;
        }
        setStatus("");
        setCopyId(v);
    }
    document.getElementById("manualCode").addEventListener("keydown", function (e) {
        if (e.key === "Enter") { e.preventDefault(); useManualCode(); }
    });

    // ---------- issue ----------
    function issueBook() {
        const erp = document.getElementById("erpInput").value.trim();
        const issueDate = document.getElementById("issueDateInput").value;
        const dueDate = document.getElementById("dueDateInput").value;
        const resultEl = document.getElementById("result");
        const btn = document.getElementById("issueBtn");

        if (!scannedCopyId) {
            alert("Pehle QR scan karo ya Copy ID daalo.");
            return;
        }
        if (!erp) {
            alert("Please enter the student's ERP ID.");
            return;
        }
        if (!issueDate) {
            alert("Please select the Issue Date.");
            return;
        }
        if (!dueDate) {
            alert("Please select the Due Date.");
            return;
        }
        if (dueDate < issueDate) {
            alert("Due Date cannot be before the Issue Date.");
            return;
        }

        btn.disabled = true;
        resultEl.innerText = "Processing...";

        fetch("issue_scanned_book.php"
            + "?copy_id=" + encodeURIComponent(scannedCopyId)
            + "&erp_id=" + encodeURIComponent(erp)
            + "&issue_date=" + encodeURIComponent(issueDate)
            + "&due_date=" + encodeURIComponent(dueDate))
            .then(response => {
                if (!response.ok) throw new Error("Server error (" + response.status + ")");
                return response.text();
            })
            .then(data => {
                resultEl.innerText = data;
                const ok = data.trim().startsWith("✅");
                resultEl.className = "fs-5 fw-bold mt-3 " + (ok ? "text-success" : "text-danger");
                // Success par button disabled rehta hai (double issue se bachne ke liye).
                // Error par wapas enable, taaki ERP ID sudhaarkar dobara try kar sako.
                if (!ok) btn.disabled = false;
            })
            .catch(err => {
                resultEl.innerText = "Error: " + err.message + ". Please try again.";
                btn.disabled = false;
            });
    }
</script>
</body>
</html>