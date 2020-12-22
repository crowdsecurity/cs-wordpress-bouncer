<html>

<h1> IP Address in cache </h1>


<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">


<style>
@media (max-width: 500px) {
.responsive-table-line td:before { content: attr(data-title); }
.responsive-table-line table, 
.responsive-table-line thead, 
.responsive-table-line tbody, 
.responsive-table-line th, 
.responsive-table-line td, 
.responsive-table-line tr { 
display: block; 
}
 
.responsive-table-line thead tr { 
display:none;
}
.responsive-table-line td { 
position: relative;
border: 0px solid transparent;
padding-left: 50% !important; 
white-space: normal;
text-align:right; 
}
 
.responsive-table-line td:before { 
position: absolute;
top: 0px;
left: 0px;
width: 45%; 
padding-right: 15px; 
height:100%;
white-space: nowrap;
text-overflow: ellipsis !important;
overflow:hidden !important;
text-align:left;
background-color:#f8f8f8;
padding:2px;
}
}
</style>
<div class="responsive-table-line" style="margin:0px auto;max-width:700px;">
<table class="table table-bordered table-condensed table-body-center" >
<thead>
<tr>
<th>IP Address</th>
</tr>
</thead>
<tbody>
<?php
$sql = "SELECT option_name FROM wp_options WHERE option_name LIKE '%_transient_crowdsec_ip%' ;";

global $wpdb;

$results = $wpdb->get_results($sql);

// output data of each row
foreach ($results as $result) {
    $split = explode('_', $result->option_name);
    echo '<tbody>';
    echo '<tr>';
    echo "<td data-title='IP Address'>".end($split).'</td>';
    echo '</tr>';
}
echo '</tbody>';
echo '</table>';
echo '</div>';
?>