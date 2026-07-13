<?php require __DIR__ . '/../../interface/user/controller/controllerSupp.php'; ?>

<div>
    <div class="card-title-row">
        <h2 class="coolveticaa">Supplier</h2>
        <button class="btn-add-green" onclick="openAddSupplierModal()">+ Add Supplier</button>
    </div>
    <div class="userList">
        <div></div>
    </div>

    <div id="supplier-toolbar"></div>
    <table class="styled-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Supplier Name</th>
                <th>Email</th>
                <th>Address</th>
                <th>Phone Number</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="supplier-tbody"></tbody>
    </table>

    <div class="pagination-container" id="supplier-pag"></div>
    
    <?php include __DIR__ . '/../../interface/user/components/modalSupplier.php' ?>

    <script src="/cardhaven/interface/user/user_filter.js"></script>
    <script src="/cardhaven/interface/user/scriptSupplier.js"></script>
</div>