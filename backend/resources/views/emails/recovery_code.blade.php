<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tu-Turismo — Recuperación de contraseña</title>
</head>
<!--
  Paleta alineada con el frontend (tailwind.config.js + index.css):
    primary : #22d3ee  (cyan-400)
    slate-900: #0f172a | slate-800: #1e293b | slate-700: #334155
    slate-600: #475569 | slate-400: #94a3b8 | slate-300: #cbd5e1
    gradient header: #0ea5e9 → #6366f1  (sky-500 → indigo-500)
-->

<body style="margin:0;padding:0;background-color:#f1f5f9;
             font-family:'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:48px 0;">
        <tr>
            <td align="center">

                <!-- Card principal — bg slate-800 / border slate-700 -->
                <table width="580" cellpadding="0" cellspacing="0" style="background-color:#ffffff;
                              border:1px solid #e2e8f0;
                              border-radius:16px;
                              overflow:hidden;
                              box-shadow:0 8px 30px rgba(0,0,0,0.10);">

                    <!-- ── HEADER: gradiente sky-500 → indigo-500 ── -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#0ea5e9,#6366f1);
                                   padding:36px 40px;text-align:center;">

                            <!-- Logotipo / nombre -->
                            <p style="margin:0;font-size:11px;font-weight:700;
                                      letter-spacing:5px;text-transform:uppercase;
                                      color:rgba(255,255,255,0.65);">
                                Tu-Turismo
                            </p>

                            <!-- Ícono candado en cyan primario -->
                            <div style="margin:14px auto 0;width:52px;height:52px;
                                        background:rgba(255,255,255,0.15);
                                        border-radius:50%;
                                        display:flex;align-items:center;justify-content:center;
                                        font-size:26px;line-height:52px;text-align:center;">

                            </div>

                            <h1 style="margin:14px 0 0;font-size:24px;font-weight:700;
                                       color:#ffffff;letter-spacing:-0.3px;">
                                Recuperación de contraseña
                            </h1>
                        </td>
                    </tr>

                    <!-- ── BODY ── -->
                    <tr>
                        <td style="padding:40px 44px 36px;">

                            <p style="margin:0 0 6px;font-size:15px;color:#475569;">
                                Hola,
                            </p>
                            <p style="margin:0 0 30px;font-size:15px;line-height:1.65;color:#475569;">
                                Recibimos una solicitud para restablecer la contraseña asociada a
                                <strong style="color:#0f172a;">{{ $email }}</strong>.
                                Usa el siguiente código de verificación; es válido durante
                                <strong style="color:#0f172a;">15 minutos</strong>:
                            </p>

                            <!-- ── Código de verificación: borde cyan primario ── -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 30px;">
                                <tr>
                                    <td align="center">
                                        <div style="display:inline-block;
                                                    background:rgba(34,211,238,0.08);
                                                    border:2px solid #22d3ee;
                                                    border-radius:14px;
                                                    padding:22px 56px;">
                                            <p style="margin:0;
                                                      font-size:44px;
                                                      font-weight:800;
                                                      letter-spacing:14px;
                                                      color:#22d3ee;
                                                      font-variant-numeric:tabular-nums;">
                                                {{ $code }}
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- ── Separador ── -->
                            <hr style="border:none;border-top:1px solid #e2e8f0;margin:0 0 26px;" />

                            <!-- ── Aviso de seguridad ── -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background:rgba(239,68,68,0.08);
                                               border-left:3px solid #ef4444;
                                               border-radius:0 8px 8px 0;
                                               padding:14px 18px;">
                                        <p style="margin:0;font-size:13px;
                                                  color:#b91c1c;line-height:1.55;">
                                            Si <strong>no</strong> solicitaste este cambio, ignora este
                                            correo. Tu contraseña actual permanece segura.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- ── FOOTER: slate-900 / slate-700 ── -->
                    <tr>
                        <td style="background:#f8fafc;
                                   padding:20px 44px;
                                   border-top:1px solid #e2e8f0;
                                   text-align:center;">
                            <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.6;">
                                Este código expirará en 15 minutos.<br />
                                © {{ date('Y') }} AdaptiCode. Todos los derechos reservados.
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