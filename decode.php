<?php
function unserialize_session_data($session_data) {
    $return_data = [];
    $offset = 0;
    while ($offset < strlen($session_data)) {
        if (!strstr(substr($session_data, $offset), "|")) {
            throw new Exception("Invalid data, remaining: " . substr($session_data, $offset));
        }
        $pos = strpos($session_data, "|", $offset);
        $num = $pos - $offset;
        $varname = substr($session_data, $offset, $num);
        $offset += $num + 1;
        $data = unserialize(substr($session_data, $offset));
        $return_data[$varname] = $data;
        $offset += strlen(serialize($data));
    }
    return $return_data;
}

function serialize_session_data($session_array) {
    $session_data = '';
    foreach ($session_array as $key => $value) {
        $session_data .= $key . '|' . serialize($value);
    }
    return $session_data;
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST["sessionData"])) {
        $session_data = $_POST["sessionData"];
        try {
            $unserialized_data = unserialize_session_data($session_data);
            $formatted_json = json_encode($unserialized_data, JSON_PRETTY_PRINT);
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    } elseif (!empty($_POST["jsonData"])) {
        $json_data = $_POST["jsonData"];
        try {
            $session_array = json_decode($json_data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON data.");
            }
            $serialized_data = serialize_session_data($session_array);
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Magento Session Encoder/Decoder</title>
    <style>
        textarea { width: calc(100% - 70px); display: inline-block; height: 150px; vertical-align: top; }
        button.copy-btn { width: 80px; display: inline-block; }
        #copyOutput { position: absolute; right: 90px; top: 30px;  width: 100px;}
        .output-container { position: relative; }
        .session-container { position: relative; }
        .session-container textarea { height: 1000px; }
        pre { background-color: #f4f4f4; padding: 10px; border: 1px solid #ddd; width: calc(100% - 90px); display: inline-block; }
        form { margin-bottom: 20px; }
    </style>    
    <script>
        function copyToClipboard(elementId) {
            var element = document.getElementById(elementId);  // Get the element
            var elementText = element.textContent;  // Get the text content from the element

            // Create a temporary textarea
            var tempTextArea = document.createElement("textarea");
            tempTextArea.value = elementText;  // Set its value to text content
            document.body.appendChild(tempTextArea);  // Append it to body

            // Select the text and copy it
            tempTextArea.select();
            document.execCommand("copy");

            // Remove the temporary textarea
            document.body.removeChild(tempTextArea);

            // alert("Copied to clipboard");  // Alert the user
        }
    </script>
</head>
<body>
    <h1>Magento Session Encoder/Decoder</h1>
    <form action="" method="post">
        <div class="output-container">
            <textarea id="sessionData" name="sessionData"></textarea>
        </div>
        <button type="submit" class="copy-btn">Decode</button>
    </form>
    <?php if (isset($formatted_json)): ?>
        <h2>Decoded JSON Output:</h2>
        <div class="output-container">
            <pre id="jsonOutput"><?= $formatted_json; ?></pre>
            <button onclick="copyToClipboard('jsonOutput')" class="copy-btn" id="copyOutput">Copy JSON</button>
        </div>
        <form action="" method="post">
            <div class="output-container">
                <textarea id="jsonData" name="jsonData"><?= $formatted_json; ?></textarea>
            </div>
            <button type="submit" class="copy-btn">Encode</button>
        </form>
    <?php elseif (isset($serialized_data)): ?>
        <h2>Re-Encoded Session Data:</h2>
        <div class="session-container">
            <textarea id="sessionOutput" name="sessionOutput"><?= htmlspecialchars($serialized_data); ?></textarea>
            <button onclick="copyToClipboard('sessionOutput')" class="copy-btn">Copy Session</button>
        </div>
    <?php elseif (isset($error_message)): ?>
        <h2>Error</h2>
        <pre><?= $error_message; ?></pre>
    <?php endif; ?>
</body>
</html>
