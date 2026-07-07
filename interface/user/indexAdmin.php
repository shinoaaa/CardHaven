<?php require __DIR__ . '/../../interface/user/controller/controllerAdmin.php'; ?>

<div>
    <div class="card-title-row">
        <h2 class="coolveticaa">Admin</h2>
        <button class="btn-add-green" onclick="openAddAdminModal()">+ Add Admin</button>
    </div>
    <div class="userList">
        <div></div>
    </div>

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
        <tbody>
            <?php if (!empty($data)): ?>
                <?php
                    $no = (($page - 1) * 7) + 1;
                    foreach ($data as $row): 
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <?php if (!empty($row['foto_profil'])): ?>
                                <img src="/cardhaven/image-profile/<?= htmlspecialchars($row['foto_profil']) ?>" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <img src="/cardhaven/assets/image/user.svg" alt="Default Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background-color: #f1f5f9; padding: 5px;">
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 600; text-align: center;"><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['no_telepon']) ?></td>
                        <td><?= ($row['created_date'] instanceof DateTime) ? $row['created_date']->format('d M Y') : '-' ?></td>
                        <td>
                            <?php if ($row['status_akun'] == 1): ?>
                                <span style="color: #27AE60; font-weight: bold;">Active</span>
                            <?php else: ?>
                                <span style="color: #E74C3C; font-weight: bold;">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-action-group">
                                <button class="btn-view-icon" onclick="openAdminModal(<?= $row['id_pengguna'] ?>)">...</button>
                                <button class="btn-edit-icon" onclick="openAdminEdit(<?= $row['id_pengguna'] ?>)"><img src="/cardhaven/assets/image/edit.svg" alt=""></button>
                                <button class="btn-delete-icon" onclick="deleteAdmin(<?= $row['id_pengguna'] ?>)"><img src="/cardhaven/assets/image/delete.svg" alt=""></button>
                                <label class="switch">
                                    <input type="checkbox" <?= $row['status_akun'] == 1 ? 'checked' : '' ?> onchange="toggleAdmin(<?= $row['id_pengguna'] ?>, this.checked, this)">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" style="text-align:center; color:#aaa; padding:20px;">No admins found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination-container">
        <?php 
            // Ambil role saat ini dari URL agar tidak hilang saat pindah halaman
            $current_role = isset($_GET['role']) ? (int)$_GET['role'] : 1; 
        ?>
        
        <?php if ($page > 1): ?>
            <a href="?role=<?= $current_role ?>&page=<?= $page - 1 ?>" class="page-link">&lt;</a>
        <?php else: ?>
            <span class="page-link disabled">&lt;</span>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 1);
        $end   = min($total_pages, $page + 1);

        if ($start > 1): ?>
            <a href="?role=<?= $current_role ?>&page=1" class="page-link <?= $page == 1 ? 'active' : '' ?>">1</a>
            <?php if ($start > 2): ?><span class="dots">...</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
            <a href="?role=<?= $current_role ?>&page=<?= $i ?>" class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($end < $total_pages): ?>
            <?php if ($end < $total_pages - 1): ?><span class="dots">...</span><?php endif; ?>
            <a href="?role=<?= $current_role ?>&page=<?= $total_pages ?>" class="page-link <?= $page == $total_pages ? 'active' : '' ?>"><?= $total_pages ?></a>
        <?php endif; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?role=<?= $current_role ?>&page=<?= $page + 1 ?>" class="page-link">&gt;</a>
        <?php else: ?>
            <span class="page-link disabled">&gt;</span>
        <?php endif; ?>
    </div>
    <?php include __DIR__ . '/../../interface/user/components/modalAdmin.php' ?>
    <script src="/cardhaven/interface/user/scriptAdmin.js"></script>
</div>