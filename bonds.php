<?php

$servername = "localhost";
$username = "root";
$password = "DominO@786#";
$dbname = "bonds";

$conn = new mysqli($servername, $username, $password, $dbname);


/* check connection */
if ($conn->connect_errno) {
    printf("Connect failed: %s\n", $mysqli->connect_error);
    exit();
}
$bondTypes = array();
$sql = "select distinct(b_type) from bonds where is_deleted=0 order by b_type asc";
$result = $conn->query($sql) or die($conn->error);
while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
    array_push($bondTypes, $row['b_type']);
}
$bondNumbersFound = array();
if (isset($_POST['checkResult'])) {
    if (!(isset($_POST['bond_types']) && $_POST['bond_types'] != '-1' && $_POST['bond_types'] != null)) {
        die("Select proper bond type");
    }
    if (!(isset($_FILES['resultfile']) && $_FILES['resultfile']['error'] == 0)) {
        die("Select result file");
    } else {
        if (move_uploaded_file($_FILES['resultfile']['tmp_name'], 'uploads/' . $_FILES['resultfile']['name'])) {
            $bondNumbers = array();
            $sql = "select b_number from bonds where is_deleted=0 and b_type=" . $_POST['bond_types'];
            $result = $conn->query($sql) or die($conn->error);
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                array_push($bondNumbers, $row['b_number']);
            }
            $resultFileContents = file_get_contents('uploads/' . $_FILES['resultfile']['name']);
            
            foreach($bondNumbers as $bondNumber){
                if (strpos($resultFileContents, $bondNumber) !== false) {
                    array_push($bondNumbersFound, $bondNumber);
                }
            }
        }
    }
}
?>
<html>
    <head>
        <title>Check Bond Result</title>
        <script type="text/javascript">
            function fetchBondList(selectBox){
                alert(selectBox.value);
            }
        </script>
    </head>
    <body>
        <h2>Check Bond Result</h2>
        <form id="upload" method="post" action="bonds.php" enctype="multipart/form-data">
    		<table>
                <tr>
                    <td>
                        Bond Type
                    </td>
                    <td>
                        <select id="bond_types" name="bond_types" onchange="fetchBondList(this);">
                        <option value="-1" selected="selected"> -- Select Bond Type -- </option>
                        <?php
                        foreach ($bondTypes as $bondType) {
                            echo '<option value="' . $bondType . '">' . $bondType . '</option>';
                        }
                        ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        Result File
                    </td>
                    <td>
                        <input type="file" name="resultfile"/>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <input type="submit" value="Check" name="checkResult"/>
                    </td>
                </tr>
            </table>
        </form>
        <?php
            if(sizeof($bondNumbersFound) > 0){
                foreach($bondNumbersFound as $bondNumberFound){
                    echo $bondNumberFound;
                }
            } else {
                echo "No Bond Number Found";
            }
        ?>
    </body>
</html>
<?php
$conn->close();
?>