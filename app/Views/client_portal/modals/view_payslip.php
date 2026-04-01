<div class="payslip-sheet">
    <div class="payslip-toolbar">
        <div class="payslip-toolbar-left">
            <button type="button" class="payslip-back modal-close" aria-label="Close payslip modal">&larr;</button>
            <h2 class="payslip-title">Timesheet</h2>
        </div>

        <div class="payslip-controls">
            <select class="payslip-range" aria-label="Payroll date range">
                <option>December 16, 2025 - December 31, 2025</option>
            </select>

            <button type="button" class="payslip-btn">
                <span aria-hidden="true">&#128424;</span>
                <span>Print Payslip</span>
            </button>

            <button type="button" class="payslip-btn">
                <span aria-hidden="true">&#11015;</span>
                <span>Download PDF</span>
            </button>
        </div>
    </div>

    <div class="payslip-card">
        <div class="payslip-company">
            <div class="payslip-meta">
                <p><span>Payroll Period</span><br>December 2025</p>
                <p><span>Payment Date</span><br>January 6, 2025</p>
            </div>

            <div class="payslip-brand">
                <h3>ECOVIS Eintax Philippines</h3>
                <p>
                    Unit 1802, The Peak Tower, Salcedo Village, 107 L.P. Leviste Street, Makati City, 1227 Metro Manila
                </p>
            </div>

            <div class="payslip-logo-wrap">
                <img src="<?= base_url('icons/ecovis_logo.png'); ?>" alt="Ecovis logo" class="payslip-logo">
            </div>
        </div>

        <hr class="payslip-divider">

        <div class="payslip-preview">
            <img src="<?= base_url('icons/payslip-sample.png'); ?>" alt="Payslip preview">
        </div>
    </div>
</div>

<style>
    #viewPayslipModal .modal-card {
        max-width: 1080px;
        padding: 22px;
        border-radius: 16px;
    }

    #viewPayslipModal > .modal-card > .modal-close,
    #viewPayslipModal > .modal-card > h2 {
        display: none;
    }

    .payslip-sheet {
        width: 100%;
        font-family: "Segoe UI", Arial, sans-serif;
        color: #1f2937;
    }

    .payslip-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 14px;
    }

    .payslip-toolbar-left {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    .payslip-back {
        width: 34px;
        height: 34px;
        border: none;
        border-radius: 8px;
        background: transparent;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
        color: #111827;
    }

    .payslip-title {
        margin: 0;
        font-size: 30px;
        font-weight: 700;
        color: #1f2937;
    }

    .payslip-controls {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .payslip-range {
        min-width: 305px;
        height: 40px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 0 12px;
        font-size: 13px;
        color: #374151;
        background: #ffffff;
    }

    .payslip-btn {
        height: 40px;
        border: none;
        border-radius: 8px;
        padding: 0 16px;
        background: #ff2d2d;
        color: #ffffff;
        font-size: 14px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }

    .payslip-card {
        border: 1px solid #d1d5db;
        border-radius: 14px;
        background: #f8f8f8;
        padding: 18px 24px 22px;
    }

    .payslip-company {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 24px;
        align-items: start;
    }

    .payslip-meta {
        display: flex;
        gap: 44px;
    }

    .payslip-meta p {
        margin: 0;
        font-size: 25px;
        line-height: 1.25;
        color: #18181b;
    }

    .payslip-meta p span {
        color: #6b7280;
        font-size: 20px;
    }

    .payslip-brand {
        align-self: end;
    }

    .payslip-brand h3 {
        margin: 12px 0 6px;
        font-size: 41px;
        line-height: 1.15;
        font-weight: 700;
        color: #1f2937;
    }

    .payslip-brand p {
        margin: 0;
        font-size: 17px;
        line-height: 1.45;
        color: #6b7280;
        max-width: 1000px;
    }

    .payslip-logo-wrap {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .payslip-logo {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .payslip-divider {
        border: none;
        border-top: 1px solid #d4d4d8;
        margin: 16px 0 18px;
    }

    .payslip-preview {
        display: block;
        height: 56vh;
        overflow: auto;
        padding: 6px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #ffffff;
    }

    .payslip-preview img {
        display: block;
        width: auto;
        max-width: none;
        min-width: 100%;
        height: auto;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #ffffff;
    }

    @media (max-width: 1024px) {
        .payslip-toolbar {
            flex-direction: column;
            align-items: flex-start;
        }

        .payslip-controls {
            justify-content: flex-start;
        }

        .payslip-company {
            grid-template-columns: 1fr;
            gap: 14px;
        }

        .payslip-meta {
            gap: 24px;
            flex-wrap: wrap;
        }

        .payslip-brand h3 {
            margin-top: 0;
        }

        .payslip-logo-wrap {
            width: 92px;
            height: 92px;
        }
    }

    @media (max-width: 640px) {
        #viewPayslipModal .modal-card {
            padding: 14px;
        }

        .payslip-title {
            font-size: 22px;
        }

        .payslip-range {
            min-width: 100%;
        }

        .payslip-btn {
            width: 100%;
            justify-content: center;
        }

        .payslip-meta p {
            font-size: 20px;
        }

        .payslip-meta p span {
            font-size: 14px;
        }

        .payslip-brand h3 {
            font-size: 24px;
        }

        .payslip-brand p {
            font-size: 14px;
        }
    }
</style>