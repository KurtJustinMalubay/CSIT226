if (isset($_POST['btnRegisterAdmin'])) {
    $fullName = trim($_POST['txtfullname']);
    $email = trim($_POST['txtemail']);
    $uniId = trim($_POST['txtuniversityId']);
    $role = $_POST['txtadminRole'];

    // 1. Insert into User table
    $s1 = $connection->prepare("INSERT INTO User(fullName, email, universityId, isAdmin, isStudent, isFaculty) VALUES(?,?,?,1,0,0)");
    $s1->bind_param("sss", $fullName, $email, $uniId);
    $s1->execute();
    
    $last_id = $connection->insert_id;
    $s1->close();

    // 2. Insert into Admin_Staff table
    $s2 = $connection->prepare("INSERT INTO Admin_Staff(adId, adminRole) VALUES(?,?)");
    $s2->bind_param("is", $last_id, $role);
    $s2->execute();
    $s2->close();
}