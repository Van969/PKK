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

    fetch('./add_siswa.php', {
      method: 'POST',
      body: new FormData(formTambah)
    })
    .then(res => res.text())
    .then(result => {
      if(result.includes('ok')){
        location.reload(); // ✅ Sukses → refresh data
      } else {
        alert('❌ Gagal menambah siswa: ' + result);
        console.error('Server Response:', result); // ✅ Debug if needed
      }
    })
    .catch(err => console.error("Tambah siswa error:", err));
  });
}

  // ==== MODAL EDIT SISWA ====

// ketika tombol edit ditekan → buka modal + isi data
document.querySelectorAll('.btn-edit').forEach(btn => {
  btn.addEventListener('click', () => {
    const id = btn.dataset.id;

    // ✅ Ambil data dari get_siswa.php (bukan update_siswa.php!)
    fetch('./get_siswa.php?id=' + id)
      .then(res => res.json())
      .then(data => {
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_nama').value = data.nama;
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
      })
      .catch(err => console.error("Gagal GET siswa:", err));
  });
});


// ✅ Saat tombol Simpan pada modal ditekan → update siswa
const formEdit = document.getElementById('formEdit');
if(formEdit){
  formEdit.addEventListener('submit', (e) => {
    e.preventDefault();

    fetch('./update_siswa.php', {
      method: 'POST',
      body: new FormData(formEdit)
    })
    .then(res => res.text())
    .then(result => {
      if(result.includes('ok')){
        location.reload(); // refresh halaman → data berubah ✅
      } else {
        alert("❌ Gagal update: " + result);
      }
    })
    .catch(err => console.error("Error UPDATE:", err));
  });
}


document.querySelectorAll('.btn-hapus').forEach(btn => {
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

// ✅ Ambil bulan & tahun dari checkbox, bukan dari Date()
const bulan = cb.dataset.bulan;
const tahun = cb.dataset.tahun;

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
  

});
function updateSidebar() {
    fetch('get_sidebar_data.php')
    .then(res => res.json())
    .then(data => {
        document.getElementById("totalPemasukanSidebar").innerText =
            "Rp " + new Intl.NumberFormat("id-ID").format(data.total_pemasukan);

        document.getElementById("saldoBersihSidebar").innerText =
            "Rp " + new Intl.NumberFormat("id-ID").format(data.saldo_bersih);
    })
    .catch(err => console.error("Error sidebar:", err));
}
