<?php require __DIR__ . '/../../interface/user/controller/controllerSuperAdmin.php'; ?>

<div>
    <div class="card-title-row">
        <h2 class="coolveticaa">Super Admin</h2>
        <button class="btn-add-green" onclick="openAddAdminModal()">+ Add Manager</button>
    </div>
    <div class="userList">
        <div></div>
    </div>

    <div id="super-toolbar"></div>
    <table class="styled-table">
        <thead>
            <tr>    
                <th>No</th>
                <th>Photo</th>
                <th>Username</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>Created Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="super-tbody"></tbody>

    </table>

    <div class="pagination-container" id="super-pag"></div>
    <?php include __DIR__ . '/../../interface/user/components/modalSuperAdmin.php' ?>
    <script src="/cardhaven/interface/user/user_filter.js"></script>
    <script src="/cardhaven/interface/user/scriptSuperAdmin.js"></script>
</div>