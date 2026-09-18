<h1>STARS Dashboard - Test View</h1>
<p>This is a minimal test view to verify the controller is working.</p>
<p>Active Recoveries: <?php echo $stats['active_recoveries']; ?></p>
<p>Pending Assessments: <?php echo $stats['pending_assessments']; ?></p>
<p>Green Gap: <?php echo $stats['gap_summary']['green']; ?></p>
<p>Amber Gap: <?php echo $stats['gap_summary']['amber']; ?></p>
<p>Red Gap: <?php echo $stats['gap_summary']['red']; ?></p>

<h3>At-Risk Students:</h3>
<ul>
<?php foreach ($stats['at_risk_students'] as $student): ?>
    <li><?php echo $student['first_name'] . ' ' . $student['last_name'] . ' - ' . $student['gap_percentage'] . '%'; ?></li>
<?php endforeach; ?>
</ul>