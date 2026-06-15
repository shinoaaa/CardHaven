<?php require __DIR__ . '/../../interface/user/controller/controllerAdmin.php'; ?>

<div>
    <div class="card-title-row">
        <h2 class="coolveticaa"></h2>
        <button class="btn-add-green" onclick="openAddAdminModal()">+ Add Admin</button>
    </div>
    <div class="userList">
        <div></div>
    </div>

    <table class="styled-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Foto</th>
                <th>Username</th>
                <th>Email</th>
                <th>Created Date</th>
                <th>Status</th>
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
                        <td>
                            <?= isset($row['created_date']) && $row['created_date'] instanceof DateTime
                                ? $row['created_date']->format('d-m-Y')
                                : '-' ?>
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
                                <button class="btn-view-icon" onclick="openAdminModal(<?= (int)$row['id_pengguna'] ?>)" title="View Detail">
                                    ...
                                </button>

                                <button class="btn-edit-icon" onclick="openAdminEdit(<?= (int)$row['id_pengguna'] ?>)" title="Edit Admin">
                                    ✏️
                                </button>

                                <button class="btn-delete-icon" onclick="deleteAdmin(<?= (int)$row['id_pengguna'] ?>)" title="Delete Admin">
                                    🗑️
                                </button>

                                <label class="switch" title="Toggle Status">
                                    <input type="checkbox" <?= ($row['status_akun'] ?? 0) == 1 ? 'checked' : '' ?> onchange="toggleAdmin(<?= (int)$row['id_pengguna'] ?>, this.checked, this)">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No admins found.</td>
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

<?php include __DIR__ . '/../../interface/user/components/modalAdmin.php' ?>
<script src="/cardhaven/interface/user/scriptAdmin.js"></script>