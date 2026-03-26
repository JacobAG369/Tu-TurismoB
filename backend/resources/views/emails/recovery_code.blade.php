<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tu-Turismo — Recuperación de contraseña</title>
</head>
<body style="margin:0;padding:0;background-color:#0f172a;font-family:'Segoe UI',Arial,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#0f172a;padding:40px 0;">
        <tr>
            <td align="center">

                <!-- Card principal -->
                <table width="560" cellpadding="0" cellspacing="0"
                    style="background:linear-gradient(145deg,#1e293b,#0f172a);
                           border:1px solid #334155;
                           border-radius:16px;
                           overflow:hidden;
                           box-shadow:0 25px 50px rgba(0,0,0,0.5);">

                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#0ea5e9,#6366f1);
                                   padding:36px 40px;text-align:center;">
                            <p style="margin:0;font-size:13px;font-weight:600;
                                      letter-spacing:4px;text-transform:uppercase;
                                      color:rgba(255,255,255,0.7);">Tu-Turismo</p>
                            <h1 style="margin:8px 0 0;font-size:26px;font-weight:700;
                                       color:#ffffff;letter-spacing:-0.5px;">
                                Recuperación de contraseña
                            </h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:40px 40px 32px;">

                            <p style="margin:0 0 8px;font-size:15px;color:#94a3b8;">
                                Hola,
                            </p>
                            <p style="margin:0 0 28px;font-size:15px;line-height:1.6;color:#cbd5e1;">
                                Recibimos una solicitud para restablecer la contraseña asociada a
                                <strong style="color:#e2e8f0;">{{ $email }}</strong>.
                                Usa el siguiente código de verificación (válido durante
                                <strong style="color:#e2e8f0;">15 minutos</strong>):
                            </p>

                            <!-- Código -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 28px;">
                                <tr>
                                    <td align="center">
                                        <div style="display:inline-block;
                                                    background:linear-gradient(135deg,rgba(14,165,233,0.15),rgba(99,102,241,0.15));
                                                    border:2px solid rgba(99,102,241,0.4);
                                                    border-radius:12px;
                                                    padding:20px 48px;">
                                            <p style="margin:0;font-size:42px;font-weight:800;
                                                       letter-spacing:12px;
                                                       color:#e2e8f0;
                                                       font-variant-numeric:tabular-nums;">
                                                {{ $code }}
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Aviso de seguridad -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background:rgba(239,68,68,0.08);
                                               border-left:3px solid #ef4444;
                                               border-radius:0 8px 8px 0;
                                               padding:14px 16px;">
                                        <p style="margin:0;font-size:13px;color:#fca5a5;line-height:1.5;">
                                            🔒 Si <strong>no</strong> solicitaste este cambio, ignora este correo.
                                            Tu contraseña actual permanece segura.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#0a1628;padding:20px 40px;
                                   border-top:1px solid #1e293b;text-align:center;">
                            <p style="margin:0;font-size:12px;color:#475569;line-height:1.5;">
                                Este código expirará en 15 minutos.<br/>
                                © {{ date('Y') }} Tu-Turismo. Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- /Card -->

            </td>
        </tr>
    </table>

</body>
</html>
