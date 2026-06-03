<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <script>
        (function() {
            const token = localStorage.getItem("token") || sessionStorage.getItem("token");
            const role = localStorage.getItem("role") || sessionStorage.getItem("role");
            
            // Jika token kosong ATAU role bukan 0 (Admin), tendang!
            if (!token || role !== "0") {
                window.location.replace("/CardHaven");
            }
        })();
    </script>
</head>
<body>
    <div class="container">
        <div class="sideBar">
            <?php include 'components/sideBar.php' ?>
        </div>
        <div style="width: 90%; height: 100%;">

        </div>
    </div>
</body>
</html>