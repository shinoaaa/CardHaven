// Start default behavior ke Buy Product persis seperti di layout
document.addEventListener('DOMContentLoaded', () => {
    switchTab('buyproduct');
});

function switchTab(tabName) {
    // Matikan semua tanda aktif di tombol
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => btn.classList.remove('active'));

    // Sembunyikan semua section tabel
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(content => content.style.display = 'none');

    // Nyalakan tanda pada tombol yang dipilih
    const targetBtn = document.querySelector(`.tab-btn[onclick="switchTab('${tabName}')"]`);
    if(targetBtn) targetBtn.classList.add('active');

    // Tampilkan data/tabel yang bersangkutan
    const targetContent = document.getElementById(`tab-${tabName}`);
    if(targetContent) targetContent.style.display = 'block';
}