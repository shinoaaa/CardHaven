<div style="flex: 1;">
    <div class="card-title-row">
        <h2 class="coolveticaa" style="font-size: 1.2rem;">Game</h2>
        <button class="btn-add-green" onclick="openAddModal()">+ Add Game</button>
    </div>

    <table class="styled-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Game Name</th>
                <th>Developer</th>
                <th>status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = $offset_game + 1;
            while ($row = sqlsrv_fetch_array($stmt_game, SQLSRV_FETCH_ASSOC)): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nama_game']) ?></td>
                <td><?= htmlspecialchars($row['developer']) ?></td>
                <td>
                    <?php if ($row['aktif'] == 1): ?>
                        <span style="color: #27AE60; font-weight: bold;">Active</span>
                    <?php else: ?>
                        <span style="color: #E74C3C; font-weight: bold;">Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="btn-action-group">
                        <button class="btn-view-icon" onclick="openDetailModal(<?= $row['id_game'] ?>)">...</button>
                        <button class="btn-edit-icon" onclick="openEditModal(<?= $row['id_game'] ?>)">✏️</button>
                        <label class="switch">
                            <input type="checkbox" 
                                <?= $row['aktif'] == 1 ? 'checked' : '' ?> 
                                onchange="toggleStatus(<?= $row['id_game'] ?>, this.checked, this)">
                            <span class="slider"></span>
                        </label>
                        <?php if ($row['is_deleted'] == 0): ?>
                            <button class="btn-delete-icon" onclick="confirmDelete(<?= $row['id_game'] ?>)">🗑️</button>
                        <?php else: ?>
                            <button class="btn-restore-icon"
                                    style="background-color: #27AE60; border:none; padding:5px; border-radius:5px; color:white; cursor:pointer;"
                                    onclick="confirmRestore(<?= $row['id_game'] ?>)">🔄</button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="pagination-container">
    <!-- Arrow Back -->
    <?php if ($page_game > 1): ?>
        <a href="javascript:void(0)" onclick="loadGamePage(<?= $page_game - 1 ?>)" class="page-link">&lt;</a>
    <?php else: ?>
        <span class="page-link disabled">&lt;</span>
    <?php endif; ?>

    <?php
    $range = 3;
    
    // Halaman Pertama & Dots
    if ($page_game > ($range + 2)) {
        echo '<a href="javascript:void(0)" onclick="loadGamePage(1)" class="page-link">1</a><span class="dots">...</span>';
    } elseif ($page_game > $range + 1) {
        echo '<a href="javascript:void(0)" onclick="loadGamePage(1)" class="page-link">1</a>';
    }

    // Loop Angka Halaman
    for ($i = max(1, $page_game - $range); $i <= min($total_pages_game, $page_game + $range); $i++) {
        $activeClass = ($i == $page_game) ? 'active' : '';
        echo '<a href="javascript:void(0)" onclick="loadGamePage('.$i.')" class="page-link '.$activeClass.'">'.$i.'</a>';
    }

    // Dots & Halaman Terakhir
    if ($page_game < ($total_pages_game - $range - 1)) {
        echo '<span class="dots">...</span><a href="javascript:void(0)" onclick="loadGamePage('.$total_pages_game.')" class="page-link">'.$total_pages_game.'</a>';
    } elseif ($page_game < $total_pages_game - $range) {
        echo '<a href="javascript:void(0)" onclick="loadGamePage('.$total_pages_game.')" class="page-link">'.$total_pages_game.'</a>';
    }
    ?>

    <!-- Arrow Next -->
    <?php if ($page_game < $total_pages_game): ?>
        <a href="javascript:void(0)" onclick="loadGamePage(<?= $page_game + 1 ?>)" class="page-link">&gt;</a>
    <?php else: ?>
        <span class="page-link disabled">&gt;</span>
    <?php endif; ?>
</div>