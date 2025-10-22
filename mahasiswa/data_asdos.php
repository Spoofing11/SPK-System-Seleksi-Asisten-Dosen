<?php
require_once '../layout/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
isLogin('admin'); 



?>

<section class="section">
    <div class="section-header d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">Update Status Pendaftaran Asisten Dosen</h1>
    </div>
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow-lg border-light rounded">
                <div class="card-body">
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once '../layout/_bottom.php'; ?>

<script src="../assets/js/page/modules-datatables.js"></script>