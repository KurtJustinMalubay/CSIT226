$sql = "SELECT User.uid, User.fullName, User.email, Admin_Staff.adminRole 
        FROM User, Admin_Staff 
        WHERE User.uid = Admin_Staff.adId";
$result = $connection->query($sql);

while($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>".$row['fullName']."</td>
            <td>".$row['adminRole']."</td>
            <td><a href='update.php?id=".$row['uid']."'>Edit</a></td>
          </tr>";
}