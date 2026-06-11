<div style="flex: 1;">
    <div class="card-title-row">
        <h2 class="coolveticaa" style="font-size: 1.2rem;">Set</h2>
        <button class="btn-add-green" onclick="openAddSetModal()">+ Add Set</button>
    </div>

    <table class="styled-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Set Name</th>
                <th>Game</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = $offset_set + 1;
            if ($stmt_set): while ($rowSet = sqlsrv_fetch_array($stmt_set, SQLSRV_FETCH_ASSOC)): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($rowSet['nama_set']) ?></td>
                <td><?= htmlspecialchars($rowSet['nama_game'] ?? 'N/A') ?></td>
                <td>
                    <?php if ($rowSet['aktif'] == 1): ?>
                        <span style="color: #27AE60; font-weight: bold;">Active</span>
                    <?php else: ?>
                        <span style="color: #E74C3C; font-weight: bold;">Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
    <div class="btn-action-group">
        <button class="btn-view-icon" onclick="openDetailSetModal(<?= $rowSet['id_set'] ?>)">...</button>
        <button class="btn-edit-icon" onclick="openEditSetModal(<?= $rowSet['id_set'] ?>)">✏️</button>
        <label class="switch">
            <input type="checkbox"
                <?= $rowSet['aktif'] == 1 ? 'checked' : '' ?>
                onchange="toggleSetStatus(<?= $rowSet['id_set'] ?>, this.checked, this)">
            <span class="slider"></span>
        </label>
        <button class="btn-delete-icon" onclick="confirmDeleteSet(<?= $rowSet['id_set'] ?>)">🗑️</button>
    </div>
</td>
            </tr>
            <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<div class="pagination-container">
    <!-- Arrow Back -->
    <?php if ($page_set > 1): ?>
        <a href="javascript:void(0)" onclick="loadSetPage(<?= $page_set - 1 ?>)" class="page-link">&lt;</a>
    <?php else: ?>
        <span class="page-link disabled">&lt;</span>
    <?php endif; ?>

    <?php
    $range = 3;
    
    // Halaman Pertama & Dots
    if ($page_set > ($range + 2)) {
        echo '<a href="javascript:void(0)" onclick="loadSetPage(1)" class="page-link">1</a><span class="dots">...</span>';
    } elseif ($page_set > $range + 1) {
        echo '<a href="javascript:void(0)" onclick="loadSetPage(1)" class="page-link">1</a>';
    }

    // Loop Angka Halaman
    for ($i = max(1, $page_set - $range); $i <= min($total_pages_set, $page_set + $range); $i++) {
        $activeClass = ($i == $page_set) ? 'active' : '';
        echo '<a href="javascript:void(0)" onclick="loadSetPage('.$i.')" class="page-link '.$activeClass.'">'.$i.'</a>';
    }

    // Dots & Halaman Terakhir
    if ($page_set < ($total_pages_set - $range - 1)) {
        echo '<span class="dots">...</span><a href="javascript:void(0)" onclick="loadSetPage('.$total_pages_set.')" class="page-link">'.$total_pages_set.'</a>';
    } elseif ($page_set < $total_pages_set - $range) {
        echo '<a href="javascript:void(0)" onclick="loadSetPage('.$total_pages_set.')" class="page-link">'.$total_pages_set.'</a>';
    }
    ?>

    <!-- Arrow Next -->
    <?php if ($page_set < $total_pages_set): ?>
        <a href="javascript:void(0)" onclick="loadSetPage(<?= $page_set + 1 ?>)" class="page-link">&gt;</a>
    <?php else: ?>
        <span class="page-link disabled">&gt;</span>
    <?php endif; ?>
</div>