<div style="flex: 1;" id="container-metode">
    <div class="card-title-row">
        <h2 class="coolveticaa" style="font-size: 1.2rem;">Payment Method</h2>
        <button class="btn-add-green" onclick="openAddMetode()">+ Add Method</button>
    </div>

    <table class="styled-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Method Name</th>
                <th>Provider</th>
                <th>Admin Fee</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no_m = $offset_metode + 1;
            if (!empty($data_metode)):
                foreach ($data_metode as $rowMetode): ?>
            <tr>
                <td><?= $no_m++ ?></td>
                <td><?= htmlspecialchars($rowMetode['nama_metode']) ?></td>
                <td><?= htmlspecialchars($rowMetode['provider'] ?? '-') ?></td>
                <td>Rp. <?= number_format($rowMetode['biaya_admin'], 0, ',', '.') ?></td>
                <td>
                    <?php if ($rowMetode['aktif'] == 1): ?>
                        <span style="color: #27AE60; font-weight: bold;">Active</span>
                    <?php else: ?>
                        <span style="color: #E74C3C; font-weight: bold;">Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="btn-action-group">
                        <button class="btn-view-icon" onclick="openDetailMetode(<?= $rowMetode['id_metode'] ?>)">...</button>
                        <button class="btn-edit-icon" onclick="openEditMetode(<?= $rowMetode['id_metode'] ?>)"><img src="/cardhaven/assets/image/edit.svg" alt=""></button>
                        <button class="btn-delete-icon" onclick="confirmDeleteMetode(<?= $rowMetode['id_metode'] ?>)"><img src="/cardhaven/assets/image/delete.svg" alt=""></button>
                        <label class="switch">
                            <input type="checkbox"
                                <?= $rowMetode['aktif'] == 1 ? 'checked' : '' ?>
                                onchange="toggleMetode(<?= $rowMetode['id_metode'] ?>, this.checked, this)">
                            <span class="slider"></span>
                        </label>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr><td colspan="6" style="text-align:center; color:#aaa; padding:20px;">No payment methods found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="pagination-container">
    <?php if ($page_metode > 1): ?>
        <a href="javascript:void(0)" onclick="loadMetodePage(<?= $page_metode - 1 ?>)" class="page-link">&lt;</a>
    <?php else: ?>
        <span class="page-link disabled">&lt;</span>
    <?php endif; ?>

    <?php
    $range = 3;
    if ($page_metode > ($range + 2)) {
        echo '<a href="javascript:void(0)" onclick="loadMetodePage(1)" class="page-link">1</a><span class="dots">...</span>';
    } elseif ($page_metode > $range + 1) {
        echo '<a href="javascript:void(0)" onclick="loadMetodePage(1)" class="page-link">1</a>';
    }

    for ($i = max(1, $page_metode - $range); $i <= min($total_pages_metode, $page_metode + $range); $i++) {
        $activeClass = ($i == $page_metode) ? 'active' : '';
        echo '<a href="javascript:void(0)" onclick="loadMetodePage('.$i.')" class="page-link '.$activeClass.'">'.$i.'</a>';
    }

    if ($page_metode < ($total_pages_metode - $range - 1)) {
        echo '<span class="dots">...</span><a href="javascript:void(0)" onclick="loadMetodePage('.$total_pages_metode.')" class="page-link">'.$total_pages_metode.'</a>';
    } elseif ($page_metode < $total_pages_metode - $range) {
        echo '<a href="javascript:void(0)" onclick="loadMetodePage('.$total_pages_metode.')" class="page-link">'.$total_pages_metode.'</a>';
    }
    ?>

    <?php if ($page_metode < $total_pages_metode): ?>
        <a href="javascript:void(0)" onclick="loadMetodePage(<?= $page_metode + 1 ?>)" class="page-link">&gt;</a>
    <?php else: ?>
        <span class="page-link disabled">&gt;</span>
    <?php endif; ?>
</div>