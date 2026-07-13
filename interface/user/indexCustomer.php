<?php require __DIR__ . '/../../interface/user/controller/controllerCustomer.php'; ?>

<div>
    <div class="card-title-row">
        
        <h2 class="coolveticaa">Customer</h2>
        <button class="btn-add-green" onclick="openAddCustomerModal()">+ Add Customer</button>
    </div>
    <div class="userList">
        <div></div>
    </div>
    <div id="customer-toolbar"></div>
    <table class="styled-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Photo</th>
                <th>Username</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>Shopping Amount</th>
                <th>Shopping Total</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="customer-tbody">
        </tbody>
    </table>

    <div class="pagination-container" id="customer-pag"></div>
    <?php include __DIR__ . '/../../interface/user/components/modalCustomer.php' ?>
    <script src="/cardhaven/interface/user/user_filter.js"></script>
    <script src="/cardhaven/interface/user/scriptCustomer.js"></script>
</div>