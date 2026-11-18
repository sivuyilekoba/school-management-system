<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $student['name'] }} — {{ $term['name'] }} report card</title>
    <style>
        @page {
            margin: 16mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.45;
            color: #111827;
            background: #ffffff;
        }

        .toolbar {
            display: block;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .toolbar a,
        .toolbar button {
            display: inline-block;
            margin-right: 8px;
            padding: 7px 14px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #f9fafb;
            color: #111827;
            font-size: 12px;
            text-decoration: none;
            cursor: pointer;
        }

        .toolbar button {
            background: #111827;
            border-color: #111827;
            color: #ffffff;
        }

        .header {
            width: 100%;
            margin-bottom: 18px;
            border-bottom: 2px solid #111827;
        }

        .header td {
            padding-bottom: 10px;
            vertical-align: bottom;
        }

        .school-name {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 0.02em;
        }

        .document-title {
            font-size: 13px;
            text-align: right;
            color: #4b5563;
        }

        .summary {
            width: 100%;
            margin-bottom: 18px;
            border-collapse: collapse;
        }

        .summary td {
            width: 33.33%;
            padding: 10px 12px;
            vertical-align: top;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .summary .label {
            display: block;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6b7280;
        }

        .summary .value {
            display: block;
            margin-top: 3px;
            font-size: 16px;
            font-weight: bold;
        }

        .summary .meta {
            display: block;
            margin-top: 2px;
            font-size: 10px;
            color: #6b7280;
        }

        .class {
            margin-bottom: 16px;
            page-break-inside: avoid;
        }

        .class-heading {
            width: 100%;
            margin-bottom: 4px;
            border-collapse: collapse;
        }

        .class-heading td {
            padding: 0 0 4px;
            font-size: 13px;
            font-weight: bold;
        }

        .class-heading td.right {
            text-align: right;
            font-size: 11px;
            font-weight: normal;
            color: #4b5563;
        }

        table.marks {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        table.marks th,
        table.marks td {
            padding: 6px 8px;
            text-align: left;
            border: 1px solid #e5e7eb;
        }

        table.marks th {
            background: #f3f4f6;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #4b5563;
        }

        table.marks tfoot td {
            background: #f9fafb;
            font-weight: bold;
        }

        .empty,
        .comment-empty {
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            font-style: italic;
            color: #6b7280;
        }

        .comment {
            margin-top: 4px;
            padding: 12px;
            border: 1px solid #e5e7eb;
            white-space: pre-line;
        }

        .section-title {
            margin: 0 0 6px;
            font-size: 13px;
            font-weight: bold;
        }

        .footer {
            margin-top: 22px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #6b7280;
        }

        @media print {
            .toolbar {
                display: none;
            }
        }
    </style>
</head>
<body>
    @unless ($forPdf)
        <div class="toolbar">
            <button type="button" onclick="window.print()">Print</button>
            <a href="{{ route('school.reports.show', ['student' => $student['id'], 'term' => $term['id']]) }}">
                Back to report card
            </a>
        </div>
    @endunless

    <table class="header">
        <tr>
            <td>
                <span class="school-name">{{ $school['name'] }}</span>
            </td>
            <td class="document-title">
                Student report card<br>
                {{ $term['school_year']['name'] }} · {{ $term['name'] }}
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td>
                <span class="label">Student</span>
                <span class="value">{{ $student['name'] }}</span>
                <span class="meta">
                    {{ $student['email'] ?? 'No email' }}@if ($student['family']) · {{ $student['family'] }}@endif
                </span>
            </td>
            <td>
                <span class="label">Overall average</span>
                <span class="value">{{ $overall['percentage'] === null ? '—' : $overall['percentage'].'%' }}</span>
                <span class="meta">
                    {{ $overall['marked_count'] }} of {{ $overall['assessments_count'] }} assessments marked
                </span>
            </td>
            <td>
                <span class="label">Attendance</span>
                <span class="value">{{ $attendance['present'] }} / {{ $attendance['total'] }}</span>
                <span class="meta">
                    {{ $attendance['late'] }} late · {{ $attendance['excused'] }} excused · {{ $attendance['absent'] }} absent
                </span>
            </td>
        </tr>
    </table>

    <p class="section-title">Assessment results</p>

    @if (count($classes) === 0)
        <p class="empty">This student is not enrolled in any classes for this term.</p>
    @else
        @foreach ($classes as $classRow)
            <div class="class">
                <table class="class-heading">
                    <tr>
                        <td>{{ $classRow['name'] }}</td>
                        <td class="right">
                            Class average:
                            {{ $classRow['percentage'] === null ? '—' : $classRow['percentage'].'%' }}
                        </td>
                    </tr>
                </table>

                @if (count($classRow['assessments']) === 0)
                    <p class="empty">No assessments were recorded for this class in this term.</p>
                @else
                    <table class="marks">
                        <thead>
                            <tr>
                                <th scope="col">Assessment</th>
                                <th scope="col">Date</th>
                                <th scope="col">Score</th>
                                <th scope="col">Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($classRow['assessments'] as $assessment)
                                <tr>
                                    <td>{{ $assessment['name'] }}</td>
                                    <td>{{ $assessment['assessed_on'] }}</td>
                                    <td>
                                        {{ $assessment['score'] === null ? '—' : $assessment['score'].' / '.$assessment['max_score'] }}
                                    </td>
                                    <td>{{ $assessment['percentage'] === null ? '—' : $assessment['percentage'].'%' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2">Class total</td>
                                <td>{{ $classRow['score'] }} / {{ $classRow['max_score'] }}</td>
                                <td>{{ $classRow['percentage'] === null ? '—' : $classRow['percentage'].'%' }}</td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            </div>
        @endforeach
    @endif

    <div class="class">
        <p class="section-title">Teacher comment</p>

        @if ($reportCard['comment'])
            <div class="comment">{{ $reportCard['comment'] }}</div>
        @else
            <p class="comment-empty">No comment has been added yet.</p>
        @endif
    </div>

    <div class="footer">
        {{ $school['name'] }} · {{ $term['school_year']['name'] }} · {{ $term['name'] }}
        ({{ $term['start_date'] }} to {{ $term['end_date'] }})
        · Generated {{ now()->toDayDateTimeString() }}
        · {{ $reportCard['published'] ? 'Published' : 'Draft' }}
    </div>
</body>
</html>
