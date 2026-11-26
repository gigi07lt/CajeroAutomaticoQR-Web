<?php if(!isset($usuario)||($usuario['rol']??'')!=='ADMIN'){http_response_code(403);exit;} ?>
<div class="grupo-campo"><label for="pin">PIN de cuatro dígitos</label><input type="password" id="pin" name="pin" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" required autocomplete="new-password"></div>
<div class="grupo-campo"><label for="confirmacion">Confirmar PIN</label><input type="password" id="confirmacion" name="confirmacion" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" required autocomplete="new-password"></div>
