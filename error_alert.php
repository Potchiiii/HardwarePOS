<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login Error</title>
  <script src="assets/third_party/sweetalert.min.js"></script>
</head>
<body>
<script>
  swal({
    title: "Login Failed",
    text: "<?php echo addslashes($message); ?>",
    icon: "error",
    button: "Go Back",
  }).then(() => {
    window.history.back();
  });
</script>
</body>
</html>
