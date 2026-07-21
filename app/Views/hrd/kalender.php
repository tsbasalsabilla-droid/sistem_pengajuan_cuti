<?= $this->include('layout/header') ?>
<?= $this->include('layout/sidebarhrd') ?>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">

<style>
    .calendar-wrapper {
        background: #ffffff;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        border: 1px solid #e2e8f0;
        margin: 20px;
        font-family: 'Inter', sans-serif;
    }

    .fc .fc-toolbar-title {
        font-size: 24px !important;
        font-weight: 700 !important;
        color: #4a3728 !important;
    }

    .fc .fc-button-primary {
        background-color: #8c6239 !important;
        border-color: #8c6239 !important;
        color: #ffffff !important;
    }

    .fc .fc-button-primary:hover {
        background-color: #6f4e2e !important;
        border-color: #6f4e2e !important;
    }

    .fc .fc-button-primary:disabled {
        background-color: #bfa38a !important;
        border-color: #bfa38a !important;
    }

    .fc th {
        background: #fbf9f6 !important;
        border: none !important;
    }

    .fc .fc-col-header-cell-cushion {
        color: #8c6239 !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        font-size: 13px;
        padding: 12px 0 !important;
        text-decoration: none !important;
    }

    .fc td {
        border: 1px solid #f0ece8 !important;
    }

    .fc .fc-daygrid-day-number {
        color: #5c4d42;
        font-weight: 500;
        font-size: 14px;
        padding: 8px !important;
        text-decoration: none !important;
    }

    .fc-v-event,
    .fc-h-event {
        border: none !important;
        border-radius: 20px !important;
        padding: 4px 12px !important;
        font-weight: 600 !important;
        font-size: 13px !important;
        margin-top: 3px !important;
    }

    .fc-daygrid-event {
        margin: 2px 5px !important;
    }

    .fc-daygrid-more-link {
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #8c6239 !important;
        padding-left: 8px !important;
        text-decoration: none !important;
    }
</style>

<div class="calendar-wrapper">
    <div id="calendar"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');

        var databaseData = <?= json_encode($upcomingLeaves ?? []) ?>;

        var eventsData = databaseData
            .map(function(item) {
                var displayTitle = item.nama_pegawai || item.nama || "Karyawan (ID: " + item.pegawai_id + ")";

                var tglMulai = item.tanggal_mulai;
                var tglSelesai = item.tanggal_selesai;

                var currentStatus = item.status ? item.status.toLowerCase() : '';
                var approvedStatuses = ['approve', 'approved', 'diterima'];
                if (!approvedStatuses.includes(currentStatus)) {
                    return null;
                }

                var bgColor = '#f5ebe0';
                var textColor = '#7b573d';

                var endDate = new Date(tglSelesai);
                endDate.setDate(endDate.getDate() + 1);
                var formattedEndDate = endDate.toISOString().split('T')[0];

                return {
                    title: "👤 " + displayTitle,
                    start: tglMulai,
                    end: formattedEndDate,
                    backgroundColor: bgColor,
                    textColor: textColor
                };
            })
            .filter(Boolean);

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'en',
            firstDay: 1,
            headerToolbar: {
                left: 'title',
                center: '',
                right: 'prev,next today'
            },
            events: eventsData,
            height: 'auto',
            dayMaxEvents: 2,
            moreLinkClick: 'popover'
        });

        calendar.render();
    });
</script>

<?= $this->include('layout/footerhrd') ?>