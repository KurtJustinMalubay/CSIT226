if (isset($_POST['btnRegisterAdmin'])) {
    $fname     = trim($_POST['txtfirstname']);
    $lname     = trim($_POST['txtlastname']);
    $email     = trim($_POST['txtemail']);
    $uniId     = trim($_POST['txtuniversityId']);
    $contact   = trim($_POST['txtcontactNum']);
    $password  = $_POST['txtpassword'];
    $secretKey = trim($_POST['secret_key']);

    if ($secretKey !== 'CIT-ADMIN-2026') {
        $error = 'Invalid secret key. Admin registration cannot proceed.';
    } elseif (empty($fname) || empty($lname) || empty($email) || empty($uniId) || empty($contact) || empty($password)) {
        $error = 'All fields are required for admin registration.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $adminRole = 'Super Admin';

        // 1. Insert into User table
        $s1 = $connection->prepare("INSERT INTO User (fname, lname, email, universityId, contactNum, password, isAdmin, isStudent) VALUES (?, ?, ?, ?, ?, ?, 1, 0)");
        $s1->bind_param("ssssss", $fname, $lname, $email, $uniId, $contact, $hashedPassword);

        if ($s1->execute()) {
            $last_id = mysqli_insert_id($connection);
            $s1->close();

            if ($last_id > 0) {
                // 2. Insert into Admin_Staff table
                $s2 = $connection->prepare("INSERT INTO Admin_Staff (adId, adminRole) VALUES (?, ?)");
                $s2->bind_param("is", $last_id, $adminRole);
                $s2->execute();
                $s2->close();
            }
        } else {
            $error = 'Admin registration failed: ' . $connection->error;
            $s1->close();
        }
    }
}