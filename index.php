<?php
include "connection.php";


checkPermission();
switch ($_POST["action"]) {
    case "addStudents":
        addStudents();
        break;
    case "getStudents":
        getStudents();
        break;
    case "addList":
        addList();
        break;
    case "getLists":
        getLists();
        break;
    case "getList":
        getList();
        break;
    case "editStudents":
        editStudents();
        break;
    case "takeItem":
        procceedItem($GLOBALS["customerId"]);
        break;
    case "giveItem":
        procceedItem($_POST["student_id"]);
        break;
    case "delStudents":
        delStudents();
        break;
    default:echo "Method isn't valid!";
}

function addStudents()
{
    $arr = explode(",", $_POST["names"]);
    foreach ($arr as $name) {
        $api_key = strrev($name);
        $insert = "INSERT INTO students (name,role_id,api_key) VALUES ('$name',1,'$api_key')";
        getDataFromDB($insert);
    }
    echo "All students happily added!";
}

function getStudents()
{
    $sql = "SELECT * FROM students";
    $data = getDataFromDB($sql);
    echo json_encode($data);
}

function addList()
{
    global $conn;
    $listName = $_POST["nameOfList"];
    $insertInLists = "INSERT INTO lists (name) VALUES('$listName');";
    $setLastId = "SET @lastID := LAST_INSERT_ID();";
    $items = explode(".", $_POST["items"]);
    $str = "";
    foreach ($items as $key => $value) {
        global $str;
        if (end($items) == $value) {
            global $str;
            $str = $str . "(" . "@lastID," . "'$value'" . ");";
            break;
        }
        $str = $str . "(" . "@lastID," . "'$value'" . "),";
    }
    $insertItems = "INSERT INTO items (list_id,name) VALUES $str";
    if (!mysqli_multi_query($conn, $insertInLists . $setLastId . $insertItems)) {
        printf("Error: %s\n", mysqli_error($conn));
    }
    echo "List was happily added!";
}

function getLists()
{
    $isLong = $_POST["long"];
    if ($isLong) {
        $sql = "SELECT * FROM lists";
        $lists = getDataFromDB($sql,false);
        foreach ($lists as $key => $value) {
            echo "<br>";
            $id = "id";
            $sql = "SELECT * FROM items WHERE list_id=$value[$id];";
            $lists[$key]['items'] = getDataFromDB($sql,false);
        }
        echo json_encode($lists);
    } else {
        $sql = "SELECT * FROM lists";
        getDataFromDB($sql);
    }
}

function getList()
{
    $id = $_POST["id"];
    $sql = "SELECT * FROM items WHERE list_id=$id";
    getDataFromDB($sql);
}

function editStudents()
{
    global $conn;
    $students = explode(',', $_POST['students']);
    foreach ($students as $key => $student) {
        $studentInfo = explode(':', $student);
        $idWhereChange = $studentInfo[0];
        $name = $studentInfo[1];
        $api_key = strrev($name);
        $sql = "UPDATE students SET name='$name',api_key='$api_key' WHERE id='$idWhereChange'";
        mysqli_query($conn, $sql);
    }
    getDataFromDB("SELECT * FROM students");
}

function delStudents()
{
    global $conn;
    $students = $_POST['students'];
    $itemsToDelete = explode(',', $students);
    foreach ($itemsToDelete as $key => $value) {
        $sql = "DELETE FROM students WHERE name='$value'";
        mysqli_query($conn, $sql);
    }
    getDataFromDB("SELECT * FROM students");
}

function procceedItem($id) 
{   
    global $conn;
    if($id !== $GLOBALS["customerId"]) {
        checkRole();
    }
    $listID = $_POST["list_id"];
    $itemID = $_POST["item_id"];
    $sql = "UPDATE items SET student_id='$id' WHERE list_id='$listID' AND id='$itemID';";
    mysqli_query($conn, $sql);
    getDataFromDB("SELECT * FROM items WHERE list_id='$listID' AND id='$itemID'");
}

function getDataFromDB($query,$needToPrint=true)
{
    global $conn;
    $result = mysqli_query($conn, $query);
    echo "<pre>";
    echo "</pre>";
    if (!$result) {
        printf("Error: %s\n", mysqli_error($conn));
        exit();
    }
    $rows = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    if($needToPrint){
        echo json_encode($rows);
    }
    return $rows;
}

function checkPermission () 
{
    global $conn;
    $api_key = $_POST["api_key"];
    $sql = "SELECT (id) FROM students WHERE api_key='$api_key'";
    $result = getDataFromDB($sql,false);
    $GLOBALS["customerId"]= $result[0]["id"];
    if (!$GLOBALS["customerId"]) {
    exit("You aren't legal user!");
    }
    
}

function checkRole() 
{
    global $conn,$customerId;
    $bigBoss = "2";
    $sql = "SELECT role_id FROM students WHERE id='$customerId'";
    $result = mysqli_query($conn, $sql);
    $role = mysqli_fetch_assoc($result)["role_id"];
    if($role !== $bigBoss) {
        exit("You aren't allowed give items.");
    }
}
