document.addEventListener('DOMContentLoaded', function () {
  const konten = document.querySelector('.konten');

  // ==== 🔍 PENCARIAN NAMA ====
  const inputCari = document.getElementById('cariNama');
  const table = document.getElementById('tbodyPembayaran');
  if (inputCari && table) {
    inputCari.addEventListener('keyup', function () {
      const filter = this.value.toLowerCase();
      table.querySelectorAll('tr').forEach(tr => {
        const nama = tr.cells[1]?.textContent.toLowerCase() || '';
        tr.style.display = nama.includes(filter) ? '' : 'none';
      });
    });
  }

  // ==== MODAL TAMBAH SISWA ====
  const btnTambah = document.getElementById('btnTambah');
  const modalTambahEl = document.getElementById('modalTambah');
  const formTambah = document.getElementById('formTambah');
  let modalTambah;
  if (btnTambah && modalTambahEl && formTambah) {
    modalTambah = new bootstrap.Modal(modalTambahEl);
    btnTambah.addEventListener('click', () => {
      formTambah.reset();
      modalTambah.show();
    });

    formTambah.addEventListener('submit', (e) => {
      e.preventDefault();
      fetch('simpan_siswa.php', { method:'POST', body: new FormData(formTambah) })
        .then(res => res.text())
        .then(text => {
          if(text.includes('ok')) location.reload();
          else alert('❌ Gagal menambah siswa: ' + text);
        })
        .catch(err => console.error(err));
    });
  }

  // ==== MODAL EDIT SISWA ====
  document.querySelectorAll('.btnEdit').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      fetch('get_siswa.php?id=' + id)
        .then(res => res.json())
        .then(data => {
          document.getElementById('edit_id').value = data.id;
          document.getElementById('edit_nama').value = data.nama;
          new bootstrap.Modal(document.getElementById('modalEdit')).show();
        });
    });
  });

  const formEdit = document.getElementById('formEdit');
  if (formEdit) {
    formEdit.addEventListener('submit', e => {
      e.preventDefault();
      fetch('update_siswa.php', { method:'POST', body: new FormData(formEdit) })
        .then(res => res.text())
        .then(text => {
          if(text.includes('ok')) location.reload();
          else alert('❌ Gagal update: ' + text);
        });
    });
  }

  // ==== HAPUS SISWA ====
  document.querySelectorAll('.btnDelete').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      if(confirm('Yakin ingin menghapus siswa beserta riwayat pembayarannya?')) {
        const fd = new FormData();
        fd.append('id', id);
        fetch('delete_siswa.php', { method:'POST', body: fd })
          .then(res => res.text())
          .then(text => {
            if(text.includes('ok')) location.reload();
            else alert('❌ Gagal hapus: ' + text);
          });
      }
    });
  });

  // ==== CHECKBOX PEMBAYARAN MINGGUAN (✅ versi stabil) ====
 document.addEventListener('change', async (e) => {
  if (!e.target.classList.contains('bayar-checkbox')) return;

  const cb = e.target;
  const id = cb.dataset.id;
  const minggu = cb.dataset.minggu;
  const now = new Date();
const bulan = now.getMonth() + 1; // 1 = Januari
const tahun = now.getFullYear();
// ✅ fix: ambil tahun sekarang
  const status = cb.checked ? 1 : 0;
  const bayarPerMinggu = 5000;

  // Update tampilan baris
  const card = cb.closest('.card');
  const semuaMinggu = card.querySelectorAll(`.bayar-checkbox[data-id="${id}"][data-bulan="${cb.dataset.bulan}"]`);
  const belumBayar = Array.from(semuaMinggu).filter(x => !x.checked).length;
  const row = cb.closest('tr');
  const totalCell = row.querySelector('td:nth-child(7)');
  totalCell.innerHTML = belumBayar === 0
    ? `<span class="text-success fw-bold">Lunas</span>`
    : `<span class="text-danger">Rp ${(belumBayar * bayarPerMinggu).toLocaleString('id-ID')}</span>`;

  // Kirim ke server
  await fetch('update_mingguan.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ id, minggu, status, bulan, tahun })
  });

  // Refresh sidebar
  updateSidebar();
});

  // ==== FUNGSI UNTUK REFRESH SIDEBAR SECARA REALTIME ====
  async function updateSidebar() {
    try {
      const res = await fetch('get_total.php');
      const data = await res.json();
      document.querySelector('#totalPemasukan').textContent = `Rp ${data.total_pemasukan.toLocaleString('id-ID')}`;
      document.querySelector('#totalPengeluaran').textContent = `Rp ${data.total_pengeluaran.toLocaleString('id-ID')}`;
      document.querySelector('#saldoBersih').textContent = `Rp ${data.saldo_bersih.toLocaleString('id-ID')}`;
    } catch (err) {
      console.error('Gagal memperbarui sidebar:', err);
    }
  }

});
