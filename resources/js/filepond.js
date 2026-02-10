import * as FilePond from 'filepond';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';

// Register Plugin
FilePond.registerPlugin(
    FilePondPluginImagePreview,
    FilePondPluginFileValidateType,
    FilePondPluginFileValidateSize
);

// Expose FilePond Core
window.FilePond = FilePond;

// =========================================================================
//  FILEPOND MANAGER (ENCAPSULATED)
// =========================================================================

window.FilePondManager = {
    // Menyimpan semua instance FilePond yang dibuat di halaman ini
    instances: [],
    
    // Counter untuk real-time button disabling (Visual only)
    activeUploadCounter: 0,

    /**
     * Helper Internal: Update tampilan tombol submit saat proses upload berjalan
     */
    _updateButtonState(btnId) {
        const btn = document.getElementById(btnId);
        if (!btn) return;

        if (this.activeUploadCounter > 0) {
            // Simpan teks asli
            if (!btn.hasAttribute('data-original-text')) {
                btn.setAttribute('data-original-text', btn.innerHTML);
            }
            btn.disabled = true;
            btn.classList.add('disabled');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengupload...';
        } else {
            btn.disabled = false;
            btn.classList.remove('disabled');
            if (btn.hasAttribute('data-original-text')) {
                btn.innerHTML = btn.getAttribute('data-original-text');
            }
        }
    },

    /**
     * 1. CREATE POND
     * Membuat instance FilePond dan mendaftarkannya ke manager
     */
    create(selector, config) {
        const inputElement = document.querySelector(selector);
        if (!inputElement) return null;

        const {
            uploadRoute, revertRoute, loadRoute, csrfToken,
            acceptedTypes = [], 
            existingFiles = [],
            maxSize = '10MB',
            submitBtnId = 'btn-submit'
        } = config;

        const pond = FilePond.create(inputElement, {
            credits: false,
            allowMultiple: true,
            allowReorder: true,
            maxFileSize: maxSize,
            acceptedFileTypes: acceptedTypes,
            labelIdle: 'Drag & Drop atau <span class="filepond--label-action">Pilih File</span>',
            files: existingFiles.map(file => ({ source: file, options: { type: 'local' } })),
            server: {
                process: {
                    url: uploadRoute,
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    onload: (res) => res,
                    onerror: (res) => {
                        this.activeUploadCounter--;
                        this._updateButtonState(submitBtnId);
                        return res;
                    }
                },
                revert: { url: revertRoute, headers: { 'X-CSRF-TOKEN': csrfToken } },
                load: { url: loadRoute + '/?file=', headers: { 'X-CSRF-TOKEN': csrfToken } }
            },
            // Event Listeners untuk Real-time Button State
            onprocessstart: () => {
                this.activeUploadCounter++;
                this._updateButtonState(submitBtnId);
            },
            onprocessfile: () => {
                this.activeUploadCounter--;
                this._updateButtonState(submitBtnId);
            },
            onremovefile: (error, file) => {
                if (file.status === 3 || file.status === 9) { // 3=LOADING, 9=PROCESSING
                   this.activeUploadCounter--; 
                   this._updateButtonState(submitBtnId);
                }
            }
        });

        // Simpan instance ke array manager
        this.instances.push(pond);
        return pond;
    },

    /**
     * 2. ATTACH FORM SUBMIT LISTENER (VALIDASI PENTING)
     * Memasang event listener ke form untuk mencegah submit jika file belum selesai
     */
    attachFormSubmit(formId, submitBtnId = 'btn-submit') {
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', (e) => {
            // Cek SEMUA pond yang terdaftar di halaman ini
            let isAnyBusy = false;

            // Loop semua instance pond untuk cek status file
            for (const pond of this.instances) {
                const files = pond.getFiles();
                // Logic User: Cek apakah ada file yang statusnya BUKAN (2=Idle/Complete atau 5=Processed)
                const pondBusy = files.some(file => file.status !== 2 && file.status !== 5);
                if (pondBusy) {
                    isAnyBusy = true;
                    break; // Ada satu saja yang busy, langsung stop
                }
            }

            if (isAnyBusy || this.activeUploadCounter > 0) {
                e.preventDefault();
                e.stopPropagation();

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Upload Belum Selesai',
                        text: 'Silakan tunggu proses upload selesai atau hapus file yang macet.',
                        showConfirmButton: true,
                        confirmButtonText: 'Mengerti',
                        timer: 5000,
                        timerProgressBar: true
                    });
                } else {
                    alert('Mohon tunggu, file sedang diupload.');
                }
            } else {
                // Jika aman, set tombol ke status "Menyimpan..."
                const btn = document.getElementById(submitBtnId);
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
                }
            }
        });
    }
};