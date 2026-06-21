<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperação de senha - Zenith</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f2fb; font-family:'Segoe UI', Helvetica, Arial, sans-serif;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f2fb; padding:40px 0;">
  <tr>
    <td align="center">

      <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:20px; overflow:hidden; box-shadow:0 8px 40px rgba(0,0,0,0.08);">

        <!-- Cabeçalho roxo -->
        <tr>
          <td align="center" style="background:linear-gradient(135deg, #A383FB, #7B5CF5); padding:32px 24px;">
            <span style="color:#ffffff; font-size:24px; font-weight:700; letter-spacing:0.5px;">Zenith</span>
          </td>
        </tr>

        <!-- Ícone de cadeado -->
        <tr>
          <td align="center" style="padding:32px 40px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0">
              <tr>
                <td width="64" height="64" align="center" valign="middle" style="background-color:#ede8ff; border-radius:50%; font-size:28px; color:#A383FB; font-weight:700;">
                  &#128274;
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- Conteúdo -->
        <tr>
          <td align="center" style="padding:24px 40px 8px;">
            <h1 style="margin:0; font-size:22px; color:#1a1a2e; font-weight:700;">
              Olá, {{ $nome }}!
            </h1>
          </td>
        </tr>

        <tr>
          <td align="center" style="padding:8px 40px 0;">
            <p style="margin:0; font-size:15px; color:#555555; line-height:1.6;">
              Seu código para recuperação de senha é:
            </p>
          </td>
        </tr>

        <!-- Código em destaque -->
        <tr>
          <td align="center" style="padding:24px 40px;">
            <span style="display:inline-block; background-color:#ede8ff; color:#A383FB; font-size:32px; font-weight:700; letter-spacing:8px; padding:18px 32px; border-radius:14px;">
              {{ $codigo }}
            </span>
          </td>
        </tr>

        <tr>
          <td align="center" style="padding:0 40px 8px;">
            <p style="margin:0; font-size:14px; color:#555555; line-height:1.6;">
              Este código expira em <strong style="color:#A383FB;">15 minutos</strong>.
            </p>
          </td>
        </tr>

        <tr>
          <td align="center" style="padding:8px 40px 32px;">
            <p style="margin:0; font-size:13px; color:#999999; line-height:1.6;">
              Se você não solicitou a recuperação de senha, ignore este e-mail.
            </p>
          </td>
        </tr>

        <!-- Rodapé -->
        <tr>
          <td align="center" style="background-color:#faf9fd; padding:20px 24px;">
            <p style="margin:0; font-size:12px; color:#999999;">
              &copy; 2026 Zenith. Todos os direitos reservados.
            </p>
          </td>
        </tr>

      </table>

    </td>
  </tr>
</table>

</body>
</html>
