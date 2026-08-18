<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        /* dompdf : CSS limite (pas de flexbox/grid). Mise en page en blocs et tableaux.
           Polices systeme uniquement (dompdf n'embarque pas Sora sans configuration). */
        @page { margin: 0; }
        body {
            font-family: 'Helvetica', sans-serif;
            color: #333333;
            margin: 0;
        }
        .frame {
            margin: 18px;
            border: 3px solid #0B66C2;
            padding: 0;
        }
        .inner {
            border: 1px solid #FFC101;
            margin: 6px;
            padding: 40px 60px;
            text-align: center;
        }
        .brand {
            color: #FF7F00;
            font-size: 15px;
            letter-spacing: 3px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .title {
            color: #1F1F1F;
            font-size: 34px;
            font-weight: bold;
            margin: 18px 0 6px;
        }
        .subtitle { color: #333333; font-size: 13px; margin-bottom: 30px; }
        .label { font-size: 12px; color: #666666; text-transform: uppercase; letter-spacing: 2px; }
        .name {
            color: #0A2540;
            font-size: 30px;
            font-weight: bold;
            margin: 8px 0 24px;
        }
        .course {
            color: #0B66C2;
            font-size: 20px;
            font-weight: bold;
            margin: 6px 0 30px;
        }
        .meta-table { width: 100%; margin-top: 30px; font-size: 12px; color: #333333; }
        .meta-table td { padding-top: 6px; }
        .serial { font-family: 'Courier', monospace; color: #1F1F1F; }
        .foot { margin-top: 14px; font-size: 10px; color: #888888; }
    </style>
</head>
<body>
    <div class="frame">
        <div class="inner">
            <div class="brand">{{ config('brand.name') }}</div>

            <div class="title">Certificat de réussite</div>
            <div class="subtitle">Ce certificat atteste que</div>

            <div class="label">Décerné à</div>
            <div class="name">{{ $learnerName }}</div>

            <div class="label">Pour avoir suivi et validé la formation</div>
            <div class="course">« {{ $courseTitle }} »</div>

            <table class="meta-table">
                <tr>
                    <td style="text-align: left; width: 50%;">
                        <span class="label">Délivré le</span><br>
                        {{ $issuedAt->translatedFormat('d F Y') }}
                    </td>
                    <td style="text-align: right; width: 50%;">
                        <span class="label">Numéro de série</span><br>
                        <span class="serial">{{ $serial }}</span>
                    </td>
                </tr>
            </table>

            <div class="foot">
                Vérifiable sur {{ $verifyUrl }}
            </div>
        </div>
    </div>
</body>
</html>
