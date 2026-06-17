<div style="width: 100%; height: 2px; background: #0F3891; margin: 40px 0;"></div>
<section class="section-padding">
    <div class="horizontal-slider draggable-slider" id="gameSlider">
        <?php foreach ($games as $game): ?>
        <div class="game-card" style="background-image: url('<?= $game['image_path'] ?>');">
            <div class="game-overlay">
                <span class="coolveticaa"><?= htmlspecialchars($game['nama_game']) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>