

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>BlockBite Preview</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      margin: 0;
      font-family: monospace;
      background-color: #000;
      color: #fff;
    }

    .status-bar {
      width: 100%;
      background-color: #121212;
      color: #ffffff;
      padding: 12px 20px;
      font-size: 14px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
      z-index: 9999;
      position: fixed;
      top: 0;
    }

    .status-bar a {
      color: #93c5fd;
      text-decoration: underline;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .success {
      color: #4ade80;
    }

    .iframe-container {
      position: fixed;
      top: 48px;
      left: 0;
      width: 100%;
      height: calc(100vh - 48px);
      z-index: 9998;
    }

    iframe {
      width: 100%;
      height: 100%;
      border: none;
    }

    .close-btn {
      cursor: pointer;
      color: #888;
    }
  </style>
</head>
<body>

  <!-- Status bar -->
  <div class="status-bar">
    <div style="display: flex; align-items: center; gap: 8px;">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="#4ade80" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2l4-4" />
        <circle cx="12" cy="12" r="10" stroke="#4ade80" stroke-width="2" />
      </svg>
      <span style="color: #93c5fd; font-weight: bold;">Connected to BlockBite API:</span>

      <a href="<?php echo esc_url($preview_url); ?>" target="_blank">
        <?php echo BlockBite\Next\NextPreview::getPreviewIcon(); ?> preview url
      </a>

      <a href="<?php echo esc_url($api_url); ?>" target="_blank" class="success">
        <?php echo BlockBite\Next\NextPreview::getPreviewIcon(); ?> api url
      </a>
    </div>

    <div class="close-btn" onclick="this.parentElement.style.display='none'" title="Close">&#10005;</div>
  </div>

  <!-- Preview Iframe -->
  <div class="iframe-container">
    <iframe
      src="<?php echo esc_url($preview_url); ?>"
      title="BlockBite Preview"
      sandbox="allow-scripts allow-same-origin allow-popups allow-forms allow-modals allow-popups-to-escape-sandbox"
      loading="lazy">
    </iframe>
  </div>

</body>
</html>
