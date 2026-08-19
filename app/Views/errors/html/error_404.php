<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= lang('Errors.pageNotFound') ?></title>
    <style>
      *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#f1f3f4;color:#202124;font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif}.card{width:min(520px,100%);padding:34px;text-align:center;background:#fff;border:1px solid #dadce0;border-radius:24px;box-shadow:0 8px 18px rgba(60,64,67,.12)}.mark{width:54px;height:54px;margin:0 auto 18px;display:grid;place-items:center;border-radius:16px;background:#e8f0fe;color:#1a73e8;font-weight:800}h1{margin:0;font-size:52px;line-height:1;color:#202124}p{margin:14px 0 0;color:#5f6368;line-height:1.65}.home{display:inline-flex;margin-top:22px;padding:10px 18px;border-radius:999px;background:#1a73e8;color:#fff;text-decoration:none;font-weight:700}
    </style>
</head>
<body><main class="card"><div class="mark">DA</div><h1>404</h1><p><?php if (ENVIRONMENT !== 'production') : ?><?= nl2br(esc($message)) ?><?php else : ?><?= lang('Errors.sorryCannotFind') ?><?php endif; ?></p><a class="home" href="/">Return home</a></main></body>
</html>
