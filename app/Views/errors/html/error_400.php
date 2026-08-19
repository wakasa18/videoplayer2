<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= lang('Errors.badRequest') ?></title>
    <style>
      *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(circle at 85% 0,rgba(66,133,244,.10),transparent 28rem),linear-gradient(180deg,#f8fafd,#eef2f6);color:#202124;font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif}.card{width:min(540px,100%);padding:38px;text-align:center;background:#fff;border:1px solid #dadce0;border-radius:28px;box-shadow:0 8px 24px rgba(60,64,67,.12);animation:enter .45s cubic-bezier(.16,1,.3,1) both}.mark{width:62px;height:62px;margin:0 auto 20px;display:grid;place-items:center;border-radius:18px;background:#e8f0fe;color:#0b57d0}.mark svg{width:30px;height:30px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}h1{margin:0;font-size:clamp(2.1rem,7vw,3.2rem);line-height:1;color:#202124;letter-spacing:-.04em}p{margin:16px auto 0;max-width:420px;color:#5f6368;line-height:1.65}.home{display:inline-flex;align-items:center;gap:8px;margin-top:24px;padding:11px 20px;border-radius:999px;background:#0b57d0;color:#fff;text-decoration:none;font-weight:700;transition:background .15s ease,transform .15s ease}.home:hover{background:#0842a0;transform:translateY(-1px)}.home svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}@keyframes enter{from{opacity:0;transform:translateY(18px) scale(.98)}to{opacity:1;transform:none}}@media(prefers-reduced-motion:reduce){.card{animation:none}.home{transition:none}}
    </style>
</head>
<body><main class="card"><div class="mark"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.5 18A4.5 4.5 0 0 1 7 9a6 6 0 0 1 11.4 2A3.5 3.5 0 1 1 18 18Z"/></svg></div><h1>400</h1><p><?php if (ENVIRONMENT !== 'production') : ?><?= nl2br(esc($message)) ?><?php else : ?><?= lang('Errors.sorryBadRequest') ?><?php endif; ?></p><a class="home" href="/"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10M9 20v-6h6v6"/></svg><span>Return home</span></a></main></body>
</html>
