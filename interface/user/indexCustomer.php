<?php require __DIR__ . '/../../interface/user/controller/controllerCustomer.php'; ?>

<div>
    <div class="card-title-row">
        <h2 class="coolveticaa">Customer Management</h2>
        <button class="btn-add-green" onclick="openAddCustomerModal()">+ Add Customer</button>
    </div>
    <div class="userList">
        <div></div>
    </div>

    <table class="styled-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Foto</th>
                <th>Nama</th>
                <th>Email</th>
                <th>No Telp</th>
                <th>Shopping Amount</th>
                <th>Shopping Total</th>
                <th>Active</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            <?php if (!empty($data)): ?>
                <?php
                    $limit = 7;
                    $no = (($page - 1) * $limit) + 1;
                ?>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <?php if (!empty($row['foto_profil'])): ?>
                                <img src="/cardhaven/image-profile/<?= htmlspecialchars($row['foto_profil']) ?>" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <img src="/cardhaven/assets/image/user.svg" alt="Default Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 600; text-align: left;">
                            <?= htmlspecialchars($row['username'] ?? '-') ?>
                        </td>
                        <td><?= htmlspecialchars($row['email'] ?? '-') ?></td>
                        <td><?= !empty($row['no_telepon']) ? htmlspecialchars($row['no_telepon']) : '-' ?></td>
                        <td><?= (int)($row['shopping_amount'] ?? 0) ?>x</td>
                        <td style="font-weight: bold; color: #27AE60;">
                            Rp <?= number_format((float)($row['shopping_total'] ?? 0), 0, ',', '.') ?>
                        </td>
                        <td>
                            <?php if (($row['status_akun'] ?? 0) == 1): ?>
                                <span class="status-badge" style="color: #27AE60; font-weight: bold;">Active</span>
                            <?php else: ?>
                                <span class="status-badge" style="color: #E74C3C; font-weight: bold;">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-action-group">
                                <button class="btn-view-icon" onclick="openCustomerModal(<?= (int)$row['id_pengguna'] ?>)" title="View Detail">
                                    ...
                                </button>

                                <button class="btn-edit-icon" onclick="openCustomerEdit(<?= (int)$row['id_pengguna'] ?>)" title="Edit Customer">
                                    ✏️
                                </button>

                                <button class="btn-delete-icon" onclick="deleteCustomer(<?= (int)$row['id_pengguna'] ?>)" title="Delete Customer">
                                    🗑️
                                </button>

                                <label class="switch" title="Toggle Status">
                                    <input type="checkbox" <?= ($row['status_akun'] ?? 0) == 1 ? 'checked' : '' ?> onchange="toggleCustomer(<?= (int)$row['id_pengguna'] ?>, this.checked, this)">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No customers found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination-container">
        <?php 
        // Bikin fungsi kecil buat nge-keep query string yang udah ada
        function getPageUrl($pageNum) {
            $queries = $_GET; // Ambil semua parameter URL saat ini
            $queries['page'] = $pageNum; // Update atau tambah parameter page
            return '?' . http_build_query($queries); // Build ulang jadi URL string
        }
        ?>

        <?php if ($page > 1): ?>
            <a href="<?= getPageUrl($page - 1) ?>" class="page-link">&lt;</a>
        <?php else: ?>
            <span class="page-link disabled">&lt;</span>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 1);
        $end   = min($total_pages, $page + 1);

        if ($start > 1):
        ?>
            <a href="<?= getPageUrl(1) ?>" class="page-link <?= $page == 1 ? 'active' : '' ?>">1</a>
            <?php if ($start > 2): ?>
                <span class="dots">...</span>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
            <a href="<?= getPageUrl($i) ?>" class="page-link <?= $i == $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($end < $total_pages): ?>
            <?php if ($end < $total_pages - 1): ?>
                <span class="dots">...</span>
            <?php endif; ?>
            <a href="<?= getPageUrl($total_pages) ?>" class="page-link <?= $page == $total_pages ? 'active' : '' ?>">
                <?= $total_pages ?>
            </a>
        <?php endif; ?>

        <?php if ($page < $total_pages): ?>
            <a href="<?= getPageUrl($page + 1) ?>" class="page-link">&gt;</a>
        <?php else: ?>
            <span class="page-link disabled">&gt;</span>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../interface/user/components/modalCustomer.php' ?>
<script src="/cardhaven/interface/user/scriptCustomer.js"></script>