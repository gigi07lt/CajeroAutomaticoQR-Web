<?php
// Componente compartido por acceso a cuenta y destinatario. No autentica por sí mismo.
$manualQr=qrManualPermitido();
?>
<div class="lector-qr" data-lector-qr data-manual="<?=$manualQr?'1':'0'?>">
    <input type="hidden" name="modo_qr" value="<?=$manualQr?'manual':'camara'?>">
    <div class="grupo-campo">
        <label for="qr-<?=h($lectorId)?>">Código QR<?=$manualQr?' (escanear o escribir)':' escaneado'?></label>
        <input id="qr-<?=h($lectorId)?>" name="qr" maxlength="15" pattern="[Tt][Kk]-[A-Fa-f0-9]{12}" autocomplete="off" required <?=$manualQr?'':'readonly'?>>
    </div>
    <div class="cajero-acciones">
        <button type="button" class="btn btn-secundario" data-qr-iniciar>Escanear QR</button>
        <button type="button" class="btn btn-secundario" data-qr-detener hidden>Detener cámara</button>
    </div>
    <div class="grupo-campo" data-qr-dispositivos hidden>
        <label for="camara-<?=h($lectorId)?>">Cámara</label>
        <select id="camara-<?=h($lectorId)?>" data-qr-camara></select>
    </div>
    <div id="lector-qr-<?=h($lectorId)?>" data-qr-area hidden>
        <video data-qr-video autoplay muted playsinline hidden></video>
        <div id="lector-html5-<?=h($lectorId)?>" data-qr-html5 hidden></div>
    </div>
    <p data-qr-estado role="status" aria-live="polite"></p>
    <noscript><p>El escaneo requiere JavaScript.</p></noscript>
</div>
