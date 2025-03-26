<?php
function session_decode_custom($session_data) {
    $return_data = [];

    $offset = 0;
    while ($offset < strlen($session_data)) {
        $pos = strpos($session_data, "|", $offset);
        if ($pos === false) {
            break;
        }
        $varname = substr($session_data, $offset, $pos - $offset);
        $offset = $pos + 1;

        $data = unserialize_custom(substr($session_data, $offset), $length);
        if ($data === false) {
            throw new Exception("Could not unserialize data at offset $offset.");
        }
        $return_data[$varname] = $data;
        $offset += $length;
    }
    return $return_data;
}

function unserialize_custom($str, &$length) {
    $data = @unserialize($str);
    if ($data !== false || $str === 'b:0;') {
        $length = strlen(serialize($data));
        return $data;
    }

    // Attempt correction of string lengths
    $fixed_str = preg_replace_callback('/s:(\d+):"(.*?)";/s', function ($matches) {
        $actual_length = strlen($matches[2]);
        return 's:' . $actual_length . ':"' . $matches[2] . '";';
    }, $str);

    $data = @unserialize($fixed_str);
    if ($data === false) {
        return false;
    }

    $length = strlen(serialize($data));
    return $data;
}

function session_encode_custom($session_array) {
    $session_data = '';
    foreach ($session_array as $key => $value) {
        $session_data .= $key . '|' . serialize($value);
    }
    return $session_data;
}

$error_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST["sessionData"])) {
        $session_data = $_POST["sessionData"];
        try {
            $unserialized_data = session_decode_custom($session_data);
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
            $serialized_data = session_encode_custom($session_array);
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
        textarea { width: calc(100% - 90px); height: 150px; }
        button.copy-btn { position: absolute; right: 10px; top: 10px; }
        .output-container { position: relative; margin-bottom: 20px; }
        pre { background-color: #f4f4f4; padding: 10px; border: 1px solid #ddd; overflow: auto; }
    </style>
    <script>
        function copyToClipboard(elementId) {
            const text = document.getElementById(elementId).textContent;
            navigator.clipboard.writeText(text).then(() => {
                alert('Copied to clipboard');
            }).catch(err => alert('Failed to copy: ', err));
        }
    </script>
</head>
<body>
    <h1>Magento Session Encoder/Decoder</h1>
    <form action="" method="post">
        <textarea id="sessionData" name="sessionData" placeholder="Paste serialized session data here"></textarea><br>
        <button type="submit">Decode</button>
    </form>

    <?php if (isset($formatted_json)): ?>
        <h2>Decoded JSON Output:</h2>
        <div class="output-container">
            <pre id="jsonOutput"><?= htmlspecialchars($formatted_json); ?></pre>
            <button onclick="copyToClipboard('jsonOutput')" class="copy-btn">Copy JSON</button>
        </div>

        <form action="" method="post">
            <textarea id="jsonData" name="jsonData"><?= htmlspecialchars($formatted_json); ?></textarea><br>
            <button type="submit">Encode</button>
        </form>
    <?php elseif (isset($serialized_data)): ?>
        <h2>Re-Encoded Session Data:</h2>
        <div class="output-container">
            <pre id="sessionOutput"><?= htmlspecialchars($serialized_data); ?></pre>
            <button onclick="copyToClipboard('sessionOutput')" class="copy-btn">Copy Session</button>
        </div>
    <?php elseif ($error_message): ?>
        <h2>Error</h2>
        <pre><?= htmlspecialchars($error_message); ?></pre>
    <?php endif; ?>
</body>
</html>
