<div style="flex: 1;">
    <div class="card-title-row">
        <h2 class="coolveticaa" style="font-size: 1.2rem;">Game</h2>
        <button class="btn-add-green" onclick="openAddModal()">+ Add Game</button>
    </div>

    <table class="styled-table">
        <thead>
            <tr>
                <th>Game Name</th>
                <th>Developer</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = sqlsrv_fetch_array($stmt_game, SQLSRV_FETCH_ASSOC)): ?>
            <tr>
                <td><?= htmlspecialchars($row['nama_game']) ?></td>
                <td><?= htmlspecialchars($row['developer']) ?></td>
                <td style="color: <?= $row['aktif'] == 1 ? '#27AE60' : '#E74C3C' ?>; font-weight: bold;">
                    <?= $row['aktif'] == 1 ? 'Active' : 'Inactive' ?>
                </td>
                <td>
                    <div class="btn-action-group">
                        <button class="btn-edit-icon" onclick="openEditModal(<?= $row['id_game'] ?>)">✏️</button>
                        <?php if ($row['aktif'] == 1): ?>
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
    <?php if ($page_game > 1): ?>
        <a href="?pp=<?= $page_produk ?>&pg=<?= $page_game-1 ?>&ps=<?= $page_set ?>&pr=<?= $page_rarity ?>" class="page-link">&lt;</a>
    <?php else: ?>
        <span class="page-link disabled">&lt;</span>
    <?php endif; ?>

    <?php
    $range = 3;
    if ($page_game > ($range + 2)) {
        echo '<a href="?pp='.$page_produk.'&pg=1&ps='.$page_set.'&pr='.$page_rarity.'" class="page-link">1</a><span class="dots">...</span>';
    } elseif ($page_game > $range + 1) {
        echo '<a href="?pp='.$page_produk.'&pg=1&ps='.$page_set.'&pr='.$page_rarity.'" class="page-link">1</a>';
    }

    for ($i = max(1, $page_game - $range); $i <= min($total_pages_game, $page_game + $range); $i++) {
        echo '<a href="?pp='.$page_produk.'&pg='.$i.'&ps='.$page_set.'&pr='.$page_rarity.'" class="page-link '.($i == $page_game ? 'active' : '').'">'.$i.'</a>';
    }

    if ($page_game < ($total_pages_game - $range - 1)) {
        echo '<span class="dots">...</span><a href="?pp='.$page_produk.'&pg='.$total_pages_game.'&ps='.$page_set.'&pr='.$page_rarity.'" class="page-link">'.$total_pages_game.'</a>';
    } elseif ($page_game < $total_pages_game - $range) {
        echo '<a href="?pp='.$page_produk.'&pg='.$total_pages_game.'&ps='.$page_set.'&pr='.$page_rarity.'" class="page-link">'.$total_pages_game.'</a>';
    }
    ?>


    <?php if ($page_game < $total_pages_game): ?>
        <a href="?pp=<?= $page_produk ?>&pg=<?= $page_game+1 ?>&ps=<?= $page_set ?>&pr=<?= $page_rarity ?>" class="page-link">&gt;</a>
    <?php else: ?>
        <span class="page-link disabled">&gt;</span>
    <?php endif; ?>
</div>
