<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h1 { font-size: 20px; }
        table { border-collapse: collapse; }
        th { background: #e9ecef; }
        th, td { border: 1px solid #777; padding: 5px; text-align: left; }
    </style>
</head>
<body>
    <h1>NCSBAS Assessment Report</h1>

    <p>
        Assessment #{{ $assessment->id }}<br>
        Assessor: {{ $assessment->user->name }}<br>
        Overall score:
        {{ $assessment->overall_score !== null
            ? number_format($assessment->overall_score * 100, 1).'%' : 'Not calculated' }}<br>
        Overall maturity: {{ $assessment->overall_maturity_level ?? 'Not calculated' }}
    </p>

    <table width="100%">
        <thead>
            <tr>
                <th>Element</th>
                <th>Yes answers</th>
                <th>Score</th>
                <th>Level</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 1; $i <= 33; $i++)
                @php($result = $results->get($i))
                @php($element = $elements->get($i)?->first())
                <tr>
                    <td>{{ $i }}. {{ $result?->element_name ?? $element?->element_name ?? 'Element '.$i }}</td>
                    <td>{{ $result?->yes_count ?? 0 }}</td>
                    <td>{{ $result?->maturity_score ?? 0 }}/3</td>
                    <td>{{ $result?->maturity_level ?? 'Not scored' }}</td>
                </tr>
            @endfor
        </tbody>
    </table>
</body>
</html>
