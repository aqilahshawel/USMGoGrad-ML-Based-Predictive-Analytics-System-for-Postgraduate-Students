<!DOCTYPE html>
<html>
<head>
    <title>New Feedback Report</title>
</head>
<body>
    <h1>New Feedback Submission Regarding Supervisors</h1>

    <p><strong>Name:</strong> {{ $feedback['name'] }}</p>
    <p><strong>Supervisor's Name:</strong> {{ $feedback['supervisor_name'] }}</p>
    <p><strong>Description:</strong></p>
    <div>{{ $feedback['description'] }}</div>

    <p>Thank you,</p>
    <p>USMGoGrad</p>
</body>
</html>
