<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUZA Clearance System - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
     
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="sidebar-brand">
                <img src="https://via.placeholder.com/45" alt="SUZA Logo"> <div>
                    <h5>SUZA CLEARANCE SYSTEM</h5>
                    <span>Admin Dashboard</span>
                </div>
            </div>
            
            <div class="sidebar-menu">
                <a href="dashboard.php" class="nav-link active">
                    <div class="nav-link-left"><i class="bi bi-grid-1x2-fill"></i> Dashboard</div>
                </a>

                <div class="menu-section-title">Management</div>
                <a href="users.php" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-people"></i> Users Management</div>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <a href="#" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-building"></i> Departments</div>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <a href="#" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-card-checklist"></i> Clearance Items</div>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <a href="#" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-mortarboard"></i> Students</div>
                    <i class="bi bi-chevron-down small"></i>
                </a>

                <div class="menu-section-title">Clearance Management</div>
                <a href="#" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-file-earmark-text"></i> All Clearance Requests</div>
                </a>
                <a href="#" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-hourglass-split"></i> Pending Approvals</div>
                    <span class="badge-danger">12</span>
                </a>
                <a href="#" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-check-circle"></i> Completed Clearances</div>
                </a>
                <a href="#" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-x-circle"></i> Rejected Clearances</div>
                </a>

                <div class="menu-section-title">System</div>
                <a href="#" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-bar-chart-line"></i> Reports & Statistics</div>
                </a>
                <a href="#" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-journal-text"></i> Audit Logs</div>
                </a>
                <a href="#" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-gear"></i> System Settings</div>
                </a>
                <a href="#" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-database"></i> Backup & Restore</div>
                </a>
            </div>
        </div>

        <div class="sidebar-footer">
            <button class="btn-logout"><i class="bi bi-box-arrow-left"></i> Logout</button>
        </div>
    </div>

    <div class="main-wrapper">
        
        <header class="top-navbar">
            <button class="btn p-0 border-0 fs-4"><i class="bi bi-list"></i></button>
            <div class="navbar-meta">
                <div><i class="bi bi-calendar3 me-1"></i> May 15, 2025</div>
                <div><i class="bi bi-clock me-1"></i> 10:30 AM</div>
                <div class="user-profile">
                    <div class="user-avatar"><i class="bi bi-person"></i></div>
                    <div class="text-start d-none d-sm-block">
                        <div class="fw-bold text-dark" style="font-size: 0.85rem; line-height:1.2;">Admin User</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">System Administrator <i class="bi bi-chevron-down ms-1"></i></div>
                    </div>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="mb-4">
                <h2 class="fw-bold text-dark h4 mb-1">Welcome back, Admin! 👋</h2>
                <p class="text-muted small m-0">Here's what's happening with the clearance system today.</p>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-xl col-md-4 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
                        <p>Total Students</p>
                        <h3>4,782</h3>
                        <p class="small">Registered students</p>
                        <div class="card-progress bg-primary"></div>
                    </div>
                </div>
                <div class="col-xl col-md-4 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-file-earmark-check-fill"></i></div>
                        <p>Total Requests</p>
                        <h3>1,246</h3>
                        <p class="small">All clearance requests</p>
                        <div class="card-progress bg-success"></div>
                    </div>
                </div>
                <div class="col-xl col-md-4 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-hourglass-split"></i></div>
                        <p>Pending Approvals</p>
                        <h3>256</h3>
                        <p class="small">Awaiting department action</p>
                        <div class="card-progress bg-warning"></div>
                    </div>
                </div>
                <div class="col-xl col-md-6 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon bg-info bg-opacity-10 text-success"><i class="bi bi-check-circle-fill text-success"></i></div>
                        <p>Completed</p>
                        <h3>876</h3>
                        <p class="small">Successfully cleared</p>
                        <div class="card-progress bg-success"></div>
                    </div>
                </div>
                <div class="col-xl col-md-6 col-sm-12">
                    <div class="stat-card">
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-x-circle-fill"></i></div>
                        <p>Rejected</p>
                        <h3>114</h3>
                        <p class="small">Requests rejected</p>
                        <div class="card-progress bg-danger"></div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-7">
                    <div class="content-card">
                        <div class="card-header-custom">
                            <h5>Clearance Requests Overview</h5>
                            <select class="form-select form-select-sm w-auto"><option>This Month</option></select>
                        </div>
                        <div style="height: 280px; position: relative;">
                            <canvas id="lineChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="content-card">
                        <div class="card-header-custom">
                            <h5>Requests by Department</h5>
                        </div>
                        <div class="row align-items-center h-100">
                            <div class="col-6" style="height: 220px; position: relative;">
                                <canvas id="donutChart"></canvas>
                            </div>
                            <div class="col-6">
                                <ul class="list-unstyled small m-0">
                                    <li class="mb-2"><i class="bi bi-circle-fill me-2 text-primary"></i> Library <span class="float-end fw-bold">320 (25.7%)</span></li>
                                    <li class="mb-2"><i class="bi bi-circle-fill me-2 text-success"></i> Finance <span class="float-end fw-bold">285 (22.9%)</span></li>
                                    <li class="mb-2"><i class="bi bi-circle-fill me-2 text-warning"></i> Accommodation <span class="float-end fw-bold">210 (16.9%)</span></li>
                                    <li class="mb-2"><i class="bi bi-circle-fill me-2 text-info"></i> Academics <span class="float-end fw-bold">250 (20.1%)</span></li>
                                    <li class="mb-2"><i class="bi bi-circle-fill me-2 text-secondary"></i> ICT Dept <span class="float-end fw-bold">145 (11.6%)</span></li>
                                    <li><i class="bi bi-circle-fill me-2 text-danger"></i> Others <span class="float-end fw-bold">36 (2.8%)</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="content-card">
                        <div class="card-header-custom">
                            <h5>Recent Clearance Requests</h5>
                            <button class="btn btn-sm btn-outline-primary fw-semibold px-3">View All</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle small m-0">
                                <thead class="table-light text-secondary">
                                    <tr>
                                        <th>#</th>
                                        <th>Student Name</th>
                                        <th>Reg. No.</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th>Date Applied</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td class="fw-semibold">Ali Juma Hassan</td>
                                        <td>SU/2021/CS/001</td>
                                        <td>Finance</td>
                                        <td><span class="badge-status pending">Pending</span></td>
                                        <td>May 15, 2025</td>
                                        <td><button class="btn btn-sm p-0 text-primary"><i class="bi bi-eye"></i></button></td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td class="fw-semibold">Amina Salim Said</td>
                                        <td>SU/2022/BA/015</td>
                                        <td>Library</td>
                                        <td><span class="badge-status approved">Approved</span></td>
                                        <td>May 15, 2025</td>
                                        <td><button class="btn btn-sm p-0 text-primary"><i class="bi bi-eye"></i></button></td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td class="fw-semibold">Mohamed Khamis</td>
                                        <td>SU/2021/IT/028</td>
                                        <td>Accommodation</td>
                                        <td><span class="badge-status pending">Pending</span></td>
                                        <td>May 14, 2025</td>
                                        <td><button class="btn btn-sm p-0 text-primary"><i class="bi bi-eye"></i></button></td>
                                    </tr>
                                    <tr>
                                        <td>4</td>
                                        <td class="fw-semibold">Fatma Ramadhan</td>
                                        <td>SU/2020/ED/003</td>
                                        <td>Academic Dept.</td>
                                        <td><span class="badge-status rejected">Rejected</span></td>
                                        <td>May 14, 2025</td>
                                        <td><button class="btn btn-sm p-0 text-primary"><i class="bi bi-eye"></i></button></td>
                                    </tr>
                                    <tr>
                                        <td>5</td>
                                        <td class="fw-semibold">Jamal Abdalla</td>
                                        <td>SU/2021/BA/045</td>
                                        <td>Finance</td>
                                        <td><span class="badge-status pending">Pending</span></td>
                                        <td>May 14, 2025</td>
                                        <td><button class="btn btn-sm p-0 text-primary"><i class="bi bi-eye"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="content-card">
                        <div class="card-header-custom">
                            <h5>System Notifications</h5>
                            <button class="btn btn-sm btn-outline-primary fw-semibold px-3">View All</button>
                        </div>
                        <div class="noti-list">
                            <div class="noti-item">
                                <div class="noti-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-person-plus"></i></div>
                                <div class="flex-grow-1">
                                    <div class="small fw-semibold text-dark">New clearance request submitted by Ali Juma Hassan</div>
                                    <div class="text-muted" style="font-size:0.75rem;">Finance Department</div>
                                </div>
                                <div class="text-muted small">10:15 AM</div>
                            </div>
                            <div class="noti-item">
                                <div class="noti-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle"></i></div>
                                <div class="flex-grow-1">
                                    <div class="small fw-semibold text-dark">Library Department approved clearance for Amina Salim Said</div>
                                    <div class="text-muted" style="font-size:0.75rem;">Request #CLR-2025-000123</div>
                                </div>
                                <div class="text-muted small">09:45 AM</div>
                            </div>
                            <div class="noti-item">
                                <div class="noti-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-exclamation-triangle"></i></div>
                                <div class="flex-grow-1">
                                    <div class="small fw-semibold text-dark">256 requests are pending approval</div>
                                    <div class="text-muted" style="font-size:0.75rem;">Please take necessary action</div>
                                </div>
                                <div class="text-muted small">09:30 AM</div>
                            </div>
                            <div class="noti-item">
                                <div class="noti-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-x-circle"></i></div>
                                <div class="flex-grow-1">
                                    <div class="small fw-semibold text-dark">Accommodation Department rejected clearance for Mohamed Khamis</div>
                                </div>
                                <div class="text-muted small">Yesterday</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="footer">
            © 2025 The State University of Zanzibar. All rights reserved.
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // 1. LINE CHART (Clearance Requests Overview)
        const ctxLine = document.getElementById('lineChart').getContext('2d');
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: ['May 1', 'May 5', 'May 10', 'May 15', 'May 20', 'May 25', 'May 30'],
                datasets: [
                    {
                        label: 'Requests',
                        data: [35, 60, 85, 78, 65, 85, 80],
                        borderColor: '#0D6EFD',
                        backgroundColor: 'transparent',
                        tension: 0.4,
                        pointRadius: 4
                    },
                    {
                        label: 'Approved',
                        data: [25, 42, 52, 58, 48, 52, 48],
                        borderColor: '#198754',
                        backgroundColor: 'transparent',
                        tension: 0.4,
                        pointRadius: 4
                    },
                    {
                        label: 'Rejected',
                        data: [10, 18, 25, 20, 12, 22, 18],
                        borderColor: '#DC3545',
                        backgroundColor: 'transparent',
                        tension: 0.4,
                        pointRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: true, position: 'top' } },
                scales: { y: { min: 0, max: 100 } }
            }
        });

        // 2. DONUT CHART (Requests by Department)
        const ctxDonut = document.getElementById('donutChart').getContext('2d');
        new Chart(ctxDonut, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [320, 285, 210, 250, 145, 36],
                    backgroundColor: ['#0D6EFD', '#198754', '#FFC107', '#6F42C1', '#0DCAF0', '#DC3545'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                cutout: '70%'
            }
        });
    </script>
</body>
</html>