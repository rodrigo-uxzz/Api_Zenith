<!DOCTYPE html>
<html>
<body>
    <h1>Olá, {{ $nome }}!</h1>
    <p>Seu código para recuperação de senha é:</p>
    <h2 style="letter-spacing: 8px;">{{ $codigo }}</h2>
    <p>Este código expira em <strong>15 minutos</strong>.</p>
    <p>Se você não solicitou a recuperação de senha, ignore este email.</p>
</body>
</html>
