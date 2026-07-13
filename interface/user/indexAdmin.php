<?php require __DIR__ . '/../../interface/user/controller/controllerAdmin.php'; ?>

<div>
    <div class="card-title-row">
        <h2 class="coolveticaa">Admin</h2>
        <button class="btn-add-green" onclick="openAddAdminModal()">+ Add Admin</button>
    </div>
    <div class="userList">
        <div></div>
    </div>
    <div id="admin-toolbar"></div>
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
        <tbody id="admin-tbody"></tbody>
    </table>

    <div class="pagination-container" id="admin-pag"></div>
    <?php include __DIR__ . '/../../interface/user/components/modalAdmin.php' ?>
    <script src="/cardhaven/interface/user/user_filter.js"></script>
    <script src="/cardhaven/interface/user/scriptAdmin.js"></script>
</div>