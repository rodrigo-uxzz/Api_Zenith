<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de E-mail - Zenith</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f2fb; font-family:Segoe UI, Arial, Helvetica, sans-serif;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f2fb;">
    <tr>
        <td align="center" style="padding:40px 20px;">

            <!-- Container -->
            <table
                role="presentation"
                width="100%"
                cellpadding="0"
                cellspacing="0"
                border="0"
                style="max-width:480px; background-color:#ffffff; border-radius:24px;"
            >

                <!-- Header -->
                <tr>
                    <td
                        align="center"
                        bgcolor="#8B6CF8"
                        style="
                            background:#8B6CF8;
                            padding:40px 24px;
                            border-top-left-radius:24px;
                            border-top-right-radius:24px;
                        "
                    >
                        <span
                            style="
                                color:#ffffff;
                                font-size:24px;
                                font-weight:700;
                                letter-spacing:0.5px;
                            "
                        >
                            Zenith
                        </span>
                    </td>
                </tr>

                <!-- Ícone -->
                <tr>
                    <td align="center" style="padding:36px 24px 0;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td
                                    align="center"
                                    valign="middle"
                                    width="88"
                                    height="88"
                                    style="
                                        background:#f0ebff;
                                        border-radius:44px;
                                        color:#8B6CF8;
                                        font-size:36px;
                                    "
                                >
                                    ✉
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Título -->
                <tr>
                    <td align="center" style="padding:28px 32px 0;">
                        <div
                            style="
                                font-size:22px;
                                font-weight:700;
                                color:#1a1a2e;
                            "
                        >
                            Olá!
                        </div>
                    </td>
                </tr>

                <!-- Texto -->
                <tr>
                    <td align="center" style="padding:18px 32px 0;">
                        <div
                            style="
                                font-size:15px;
                                line-height:24px;
                                color:#666666;
                            "
                        >
                            Seu código de verificação é:
                        </div>
                    </td>
                </tr>

                <!-- Código -->
                <tr>
                    <td align="center" style="padding:28px 24px;">
                        <table
                            role="presentation"
                            cellpadding="0"
                            cellspacing="0"
                            border="0"
                            style="
                                background:#f0ebff;
                                border-radius:16px;
                            "
                        >
                            <tr>
                                <td
                                    align="center"
                                    style="
                                        padding:20px 32px;
                                        font-size:34px;
                                        font-weight:700;
                                        letter-spacing:8px;
                                        color:#8B6CF8;
                                    "
                                >
                                    {{ $codigo }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Expiração -->
                <tr>
                    <td align="center" style="padding:0 32px 36px;">
                        <div
                            style="
                                font-size:14px;
                                line-height:22px;
                                color:#666666;
                            "
                        >
                            Este código expira em
                            <strong style="color:#8B6CF8;">
                                15 minutos
                            </strong>.
                        </div>
                    </td>
                </tr>

                <!-- Rodapé -->
                <tr>
                    <td
                        align="center"
                        style="
                            background:#faf9fd;
                            padding:24px;
                            border-bottom-left-radius:24px;
                            border-bottom-right-radius:24px;
                        "
                    >
                        <div
                            style="
                                font-size:12px;
                                color:#999999;
                            "
                        >
                            © 2026 Zenith. Todos os direitos reservados.
                        </div>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>