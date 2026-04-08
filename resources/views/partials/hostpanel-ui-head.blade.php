<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<style>
    .toastify {
        border-radius: 14px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.22);
        font-size: 0.92rem;
        font-weight: 600;
        padding: 14px 18px;
    }

    .hp-swal-popup {
        border-radius: 20px;
    }

    .hp-swal-confirm,
    .hp-swal-cancel {
        border-radius: 12px !important;
        font-weight: 700 !important;
        padding: 0.8rem 1.2rem !important;
    }

    .hp-dropzone {
        position: relative;
        border: 2px dashed #cbd5e1;
        border-radius: 20px;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        transition: border-color .2s ease, background .2s ease, transform .2s ease, box-shadow .2s ease;
    }

    .hp-dropzone.is-dragging {
        border-color: #2563eb;
        background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
        box-shadow: 0 18px 45px rgba(37, 99, 235, 0.15);
        transform: translateY(-1px);
    }

    .hp-dropzone.is-uploading {
        border-color: #0f766e;
    }

    .hp-progress-track {
        height: 12px;
        overflow: hidden;
        border-radius: 999px;
        background: #e2e8f0;
    }

    .hp-progress-bar {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #0f766e, #14b8a6, #38bdf8);
        background-size: 200% 100%;
        animation: hp-progress-stripe 1.1s linear infinite;
        transition: width .2s ease;
    }

    .hp-file-pill {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-weight: 600;
        padding: .45rem .85rem;
    }

    @keyframes hp-progress-stripe {
        from { background-position: 0 0; }
        to { background-position: 200% 0; }
    }
</style>
