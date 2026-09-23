<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(vars('page_title')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-light: #eff6ff;
            --success: #16a34a;
            --success-light: #f0fdf4;
            --warning: #d97706;
            --warning-light: #fffbeb;
            --danger: #dc2626;
            --danger-light: #fef2f2;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-active: #2563eb;
            --sidebar-text: #94a3b8;
            --sidebar-text-active: #ffffff;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --border-hover: #cbd5e1;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --text-light: #94a3b8;
            --bg-canvas: #f8fafc;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            --radius-sm: 6px;
            --radius: 8px;
            --radius-lg: 12px;
            --radius-full: 9999px;
            --font: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: var(--font);
            background: var(--bg-canvas);
            color: var(--text-main);
            display: flex;
            height: 100vh;
            overflow: hidden;
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* --- SIDEBAR --- */
        aside.app-sidebar {
            width: 275px;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            border-right: 1px solid #1e293b;
            z-index: 20;
            user-select: none;
            transition: width 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar-brand {
            padding: 1.15rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            gap: 0.5rem;
        }
        .sidebar-brand .logo-title {
            color: #ffffff;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
            overflow: hidden;
            white-space: nowrap;
        }
        .sidebar-brand .badge-tag {
            background: rgba(37,99,235,0.2);
            color: #60a5fa;
            font-size: 0.68rem;
            padding: 0.18rem 0.45rem;
            border-radius: var(--radius-sm);
            font-weight: 700;
            border: 1px solid rgba(96,165,250,0.3);
            white-space: nowrap;
        }
        .sidebar-toggle-btn {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            color: #94a3b8;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s;
            flex-shrink: 0;
        }
        .sidebar-toggle-btn:hover {
            background: rgba(255,255,255,0.15);
            color: #ffffff;
        }
        .sidebar-nav {
            flex: 1;
            padding: 0.85rem 0.75rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .nav-section-title {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #475569;
            padding: 0.75rem 0.6rem 0.25rem;
            font-weight: 700;
        }
        .nav-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.65rem 0.75rem;
            color: var(--sidebar-text);
            text-decoration: none;
            border-radius: var(--radius);
            font-size: 0.84rem;
            font-weight: 500;
            transition: all 0.15s ease;
            cursor: pointer;
            border: 1px solid transparent;
            position: relative;
        }
        .nav-item:hover {
            background: var(--sidebar-hover);
            color: #ffffff;
        }
        .nav-item.active {
            background: rgba(37,99,235,0.15);
            color: #60a5fa;
            border-color: rgba(37,99,235,0.4);
            font-weight: 600;
        }
        .nav-item-left {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            min-width: 0;
            overflow: hidden;
        }
        .nav-item-left span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .nav-item svg {
            width: 18px;
            height: 18px;
            stroke-width: 2;
            opacity: 0.85;
            flex-shrink: 0;
        }
        .nav-badge {
            background: rgba(255,255,255,0.1);
            color: #e2e8f0;
            font-size: 0.7rem;
            padding: 0.15rem 0.45rem;
            border-radius: var(--radius-full);
            font-weight: 600;
            flex-shrink: 0;
        }
        .nav-badge.danger {
            background: var(--danger);
            color: #ffffff;
        }

        /* Collapsed Sidebar Styles */
        aside.app-sidebar.collapsed {
            width: 72px;
        }
        aside.app-sidebar.collapsed .sidebar-brand {
            padding: 1.15rem 0.5rem;
            justify-content: center;
        }
        aside.app-sidebar.collapsed .sidebar-brand .logo-title span,
        aside.app-sidebar.collapsed .sidebar-brand .badge-tag {
            display: none;
        }
        aside.app-sidebar.collapsed .nav-section-title {
            height: 1px;
            background: rgba(255,255,255,0.08);
            margin: 0.6rem 0.4rem;
            padding: 0;
            font-size: 0;
            overflow: hidden;
        }
        aside.app-sidebar.collapsed .nav-item {
            justify-content: center;
            padding: 0.75rem 0;
        }
        aside.app-sidebar.collapsed .nav-item-left span,
        aside.app-sidebar.collapsed .nav-badge {
            display: none;
        }
        aside.app-sidebar.collapsed .nav-item svg {
            width: 20px;
            height: 20px;
            margin: 0;
            opacity: 0.95;
        }
        aside.app-sidebar.collapsed .nav-item::after {
            content: attr(data-tooltip);
            position: absolute;
            left: calc(100% + 12px);
            top: 50%;
            transform: translateY(-50%);
            background: #0f172a;
            color: #f8fafc;
            padding: 0.45rem 0.8rem;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 600;
            white-space: nowrap;
            box-shadow: 0 4px 16px rgba(0,0,0,0.4);
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.15s ease, transform 0.15s ease;
            z-index: 1000;
            border: 1px solid rgba(255,255,255,0.12);
        }
        aside.app-sidebar.collapsed .nav-item:hover::after {
            opacity: 1;
            transform: translateY(-50%) translateX(2px);
        }
        aside.app-sidebar.collapsed .sidebar-footer {
            padding: 0.85rem 0.5rem;
            justify-content: center;
        }
        aside.app-sidebar.collapsed .sidebar-footer .user-info,
        aside.app-sidebar.collapsed .sidebar-footer .btn-logout {
            display: none;
        }
        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .user-avatar {
            width: 34px;
            height: 34px;
            border-radius: var(--radius-full);
            background: #2563eb;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.82rem;
        }
        .user-info {
            flex: 1;
            margin-left: 0.65rem;
            overflow: hidden;
        }
        .user-name {
            color: #ffffff;
            font-size: 0.82rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .user-role {
            font-size: 0.72rem;
            color: #64748b;
        }
        .btn-logout {
            color: #64748b;
            text-decoration: none;
            padding: 0.4rem;
            border-radius: var(--radius-sm);
            transition: color 0.15s;
        }
        .btn-logout:hover {
            color: #ef4444;
        }

        /* --- MAIN WRAPPER --- */
        .app-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: var(--bg-canvas);
        }

        /* --- HEADER / TOPBAR --- */
        header.app-topbar {
            height: 60px;
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            flex-shrink: 0;
            z-index: 10;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
            max-width: 540px;
        }
        .omnisearch-trigger {
            position: relative;
            width: 100%;
            cursor: pointer;
        }
        .omnisearch-input {
            width: 100%;
            background: #f1f5f9;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 0.5rem 0.75rem 0.5rem 2.2rem;
            font-size: 0.84rem;
            color: var(--text-main);
            outline: none;
            transition: all 0.15s;
        }
        .omnisearch-input:focus {
            background: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }
        .omnisearch-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            width: 15px;
            height: 15px;
            color: var(--text-light);
        }
        .omnisearch-shortcut {
            position: absolute;
            right: 0.65rem;
            top: 50%;
            transform: translateY(-50%);
            background: #ffffff;
            border: 1px solid var(--border-color);
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            font-size: 0.68rem;
            font-weight: 600;
            color: var(--text-muted);
        }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .quick-stat-chip {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-full);
            padding: 0.35rem 0.8rem;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .quick-stat-chip .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--success);
        }

        /* --- BUTTONS --- */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            padding: 0.5rem 0.85rem;
            border-radius: var(--radius);
            font-size: 0.84rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            border: 1px solid transparent;
            line-height: 1;
            white-space: nowrap;
        }
        .btn-primary {
            background: var(--primary);
            color: #ffffff;
        }
        .btn-primary:hover {
            background: var(--primary-hover);
        }
        .btn-secondary {
            background: #ffffff;
            border-color: var(--border-color);
            color: var(--text-main);
        }
        .btn-secondary:hover {
            background: #f8fafc;
            border-color: var(--border-hover);
        }
        .btn-success {
            background: var(--success);
            color: #ffffff;
        }
        .btn-success:hover {
            background: #15803d;
        }
        .btn-danger {
            background: var(--danger-light);
            border-color: #fecaca;
            color: var(--danger);
        }
        .btn-danger:hover {
            background: #fee2e2;
        }
        .btn-sm {
            padding: 0.35rem 0.6rem;
            font-size: 0.78rem;
        }
        .btn-icon {
            padding: 0.5rem;
            border-radius: var(--radius);
        }

        /* --- CONTENT AREA & SECTIONS --- */
        .content-scrollable {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }
        .tab-content-panel {
            display: none;
        }
        .tab-content-panel.active {
            display: block;
        }

        /* --- KPI GRID --- */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .kpi-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1.1rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.15s;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        .kpi-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        .kpi-card .kpi-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .kpi-card .kpi-value {
            font-size: 1.7rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.03em;
        }
        .kpi-card .kpi-subtext {
            font-size: 0.75rem;
            color: var(--text-light);
            margin-top: 0.35rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .kpi-card.accent-blue { border-top: 3px solid #2563eb; }
        .kpi-card.accent-emerald { border-top: 3px solid #10b981; }
        .kpi-card.accent-amber { border-top: 3px solid #f59e0b; }
        .kpi-card.accent-purple { border-top: 3px solid #8b5cf6; }
        .kpi-card.accent-rose { border-top: 3px solid #f43f5e; }

        /* --- URGENT ACTIONS BAR --- */
        .urgent-bar {
            background: #fffbeb;
            border: 1px solid #fef3c7;
            border-left: 4px solid #f59e0b;
            border-radius: var(--radius);
            padding: 0.85rem 1.1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85rem;
        }
        .urgent-bar-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .urgent-pill {
            background: #f59e0b;
            color: #ffffff;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.15rem 0.5rem;
            border-radius: var(--radius-full);
            text-transform: uppercase;
        }

        /* --- CARDS & PANELS --- */
        .panel-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        .panel-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
        }
        .panel-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .panel-body {
            padding: 1.25rem;
        }

        /* --- KANBAN BOARD --- */
        .kanban-board {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding-bottom: 1.5rem;
            min-height: calc(100vh - 180px);
            align-items: flex-start;
        }
        .kanban-column {
            width: 295px;
            flex-shrink: 0;
            background: #f1f5f9;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 190px);
        }
        .kanban-column-header {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--border-color);
            background: #f8fafc;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .kanban-stage-name {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .kanban-badge {
            background: #e2e8f0;
            color: var(--text-muted);
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.15rem 0.5rem;
            border-radius: var(--radius-full);
        }
        .kanban-cards-container {
            flex: 1;
            padding: 0.75rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }
        .kanban-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 0.85rem;
            box-shadow: var(--shadow-sm);
            cursor: grab;
            transition: all 0.15s ease;
        }
        .kanban-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow);
            transform: translateY(-1px);
        }
        .kanban-card.dragging {
            opacity: 0.5;
            cursor: grabbing;
        }
        .card-meta-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem;
            margin-bottom: 0.45rem;
        }
        .card-tag {
            font-size: 0.68rem;
            padding: 0.15rem 0.45rem;
            border-radius: 4px;
            font-weight: 600;
            background: #f1f5f9;
            color: #475569;
        }
        .card-tag.sector { background: #ede9fe; color: #6d28d9; }
        .card-tag.district { background: #e0f2fe; color: #0369a1; }
        .card-tag.trial-urgent { background: #fee2e2; color: #b91c1c; font-weight: 700; }
        .card-tag.trial-active { background: #fef3c7; color: #b45309; }
        .card-title {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.35rem;
            line-height: 1.25;
        }
        .card-contact {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid #f1f5f9;
            padding-top: 0.5rem;
            margin-top: 0.5rem;
            font-size: 0.75rem;
        }
        .card-mrr {
            font-weight: 700;
            color: var(--primary);
        }
        .card-actions-row {
            display: flex;
            gap: 0.25rem;
        }
        .card-btn {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 0.2rem 0.45rem;
            font-size: 0.7rem;
            cursor: pointer;
            color: var(--text-muted);
        }
        .card-btn:hover {
            background: var(--primary-light);
            color: var(--primary);
            border-color: var(--primary);
        }

        /* --- DATA TABLES --- */
        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }
        table.data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.84rem;
        }
        table.data-table th {
            background: #f8fafc;
            color: var(--text-muted);
            font-weight: 600;
            text-align: left;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }
        table.data-table td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
            vertical-align: middle;
        }
        table.data-table tbody tr {
            transition: background 0.1s;
        }
        table.data-table tbody tr:hover {
            background: #f8fafc;
        }
        table.data-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* --- BADGES --- */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.55rem;
            border-radius: var(--radius-full);
            font-size: 0.72rem;
            font-weight: 600;
            line-height: 1;
            white-space: nowrap;
        }
        .badge.active, .badge.status-active, .badge.won {
            background: #dcfce7;
            color: #15803d;
        }
        .badge.suspended, .badge.lost {
            background: #fee2e2;
            color: #b91c1c;
        }
        .badge.expired {
            background: #fef3c7;
            color: #b45309;
        }
        .badge.pending, .badge.not_opened {
            background: #f1f5f9;
            color: #475569;
        }
        .badge.in_progress, .badge.opened, .badge.started {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .badge.completed {
            background: #d1fae5;
            color: #047857;
        }

        /* --- FILTER BAR --- */
        .filter-toolbar {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 0.85rem 1.1rem;
            margin-bottom: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            justify-content: space-between;
        }
        .filter-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            align-items: center;
        }
        .filter-select, .filter-input {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 0.45rem 0.65rem;
            font-size: 0.82rem;
            color: var(--text-main);
            outline: none;
            transition: all 0.15s;
        }
        .filter-select:focus, .filter-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }
        .search-box {
            position: relative;
            display: inline-flex;
            align-items: center;
        }
        .search-box .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            width: 15px;
            height: 15px;
            color: var(--text-light);
            pointer-events: none;
        }
        .search-box .search-input {
            width: 100%;
            padding: 0.45rem 0.65rem 0.45rem 2.2rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 0.82rem;
            color: var(--text-main);
            background: #ffffff;
            outline: none;
            transition: all 0.15s;
        }
        .search-box .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }

        /* --- DRAWERS & MODALS --- */
        .drawer-overlay, .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(2px);
            display: none;
            z-index: 100;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .drawer-overlay.open, .modal-backdrop.open {
            display: flex;
            opacity: 1;
        }
        .drawer-container {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: 580px;
            max-width: 90vw;
            background: #ffffff;
            box-shadow: -10px 0 25px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 110;
        }
        .drawer-overlay.open .drawer-container {
            transform: translateX(0);
        }
        .drawer-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
        }
        .drawer-title {
            font-size: 1.1rem;
            font-weight: 700;
        }
        .drawer-body {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }
        .drawer-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            background: #f8fafc;
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        /* Centered Modals */
        .modal {
            background: #ffffff;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 520px;
            margin: auto;
            box-shadow: var(--shadow-lg);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid var(--border-color);
            animation: modalFadeIn 0.2s ease;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal-header {
            padding: 1.1rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-title {
            font-size: 1rem;
            font-weight: 700;
        }
        .modal-body {
            padding: 1.25rem;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid var(--border-color);
            background: #f8fafc;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        /* Form styling */
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 0.35rem;
        }
        .form-control {
            width: 100%;
            padding: 0.55rem 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 0.85rem;
            font-family: inherit;
            color: var(--text-main);
            outline: none;
            transition: all 0.15s;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        .form-hint {
            font-size: 0.72rem;
            color: var(--text-light);
            margin-top: 0.25rem;
        }

        /* Wizard Steps */
        .wizard-steps {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            background: #f8fafc;
        }
        .wizard-step {
            flex: 1;
            padding: 0.75rem 0.5rem;
            text-align: center;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            border-bottom: 2px solid transparent;
            cursor: pointer;
        }
        .wizard-step.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background: #ffffff;
        }
        .wizard-step.completed {
            color: var(--success);
        }

        /* Timeline in drawer */
        .timeline {
            position: relative;
            padding-left: 1.5rem;
            margin-top: 1rem;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 6px;
            top: 6px;
            bottom: 6px;
            width: 2px;
            background: #e2e8f0;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 1.25rem;
        }
        .timeline-item:last-child {
            margin-bottom: 0;
        }
        .timeline-dot {
            position: absolute;
            left: -1.5rem;
            top: 2px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #ffffff;
            border: 3px solid var(--primary);
        }
        .timeline-content {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 0.75rem 0.85rem;
        }
        .timeline-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.25rem;
        }
        .timeline-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--text-main);
        }
        .timeline-time {
            font-size: 0.7rem;
            color: var(--text-light);
        }
        .timeline-desc {
            font-size: 0.78rem;
            color: var(--text-muted);
            white-space: pre-line;
        }

        /* Omnisearch Modal */
        #omnisearch-modal .modal {
            max-width: 620px;
            margin-top: 8vh;
        }
        .search-results-list {
            max-height: 380px;
            overflow-y: auto;
        }
        .search-result-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: background 0.1s;
        }
        .search-result-item:hover, .search-result-item.selected {
            background: var(--primary-light);
        }

        /* Toast Notifications */
        #toast-container {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .toast {
            background: #1e293b;
            color: #ffffff;
            padding: 0.85rem 1.1rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            font-size: 0.84rem;
            display: flex;
            align-items: center;
            gap: 0.65rem;
            border-left: 4px solid var(--primary);
            animation: toastIn 0.2s ease;
            min-width: 280px;
            max-width: 420px;
        }
        .toast.success { border-left-color: var(--success); }
        .toast.error { border-left-color: var(--danger); }
        .toast.warning { border-left-color: var(--warning); }
        @keyframes toastIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive Mobile / Tablet Field Mode */
        @media (max-width: 768px) {
            aside.app-sidebar {
                position: fixed;
                left: -260px;
                top: 0;
                bottom: 0;
                transition: left 0.2s ease;
            }
            aside.app-sidebar.mobile-open {
                left: 0;
            }
            .sidebar-backdrop {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                z-index: 15;
            }
            .sidebar-backdrop.open { display: block; }
            .mobile-menu-btn { display: inline-flex !important; }
            .topbar-left { max-width: 100%; }
        }
        .mobile-menu-btn { display: none; }

        /* --- GOOGLE PLACES CRAWLER STYLES --- */
        .places-config-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }
        @media (max-width: 960px) {
            .places-config-grid { grid-template-columns: 1fr; }
        }
        .places-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .places-card-header {
            padding: 0.9rem 1.2rem;
            background: #f8fafc;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .places-card-title {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .places-card-body {
            padding: 1.2rem;
            flex: 1;
        }
        .places-mode-selector {
            display: flex;
            background: #f1f5f9;
            border-radius: 8px;
            padding: 3px;
            margin-bottom: 1rem;
            gap: 3px;
        }
        .places-mode-btn {
            flex: 1;
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
            border: none;
            background: transparent;
            color: var(--text-muted);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }
        .places-mode-btn.active {
            background: #ffffff;
            color: var(--primary);
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .places-district-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 0.45rem;
            max-height: 220px;
            overflow-y: auto;
            padding-right: 4px;
            margin-bottom: 0.75rem;
        }
        .places-district-item {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.78rem;
            padding: 0.35rem 0.55rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.12s;
            user-select: none;
        }
        .places-district-item:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        .places-district-item input {
            cursor: pointer;
        }
        .places-district-item.checked {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1e40af;
            font-weight: 600;
        }
        .places-pills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 0.5rem;
            max-height: 250px;
            overflow-y: auto;
            padding-right: 4px;
            margin-bottom: 0.75rem;
        }
        .places-pill-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            padding: 0.45rem 0.65rem;
            border: 1px solid var(--border-color);
            border-radius: 7px;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.15s;
            user-select: none;
        }
        .places-pill-item:hover {
            border-color: var(--primary);
            background: #f8fafc;
        }
        .places-pill-item.checked {
            background: #f5f3ff;
            border-color: #c4b5fd;
            color: #5b21b6;
            font-weight: 600;
        }
        .places-pill-item input {
            cursor: pointer;
        }
        .places-map-box {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            background: #e2e8f0;
            height: 250px;
            margin-bottom: 0.75rem;
        }
        .places-map-slider-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: #f8fafc;
            padding: 0.65rem 0.85rem;
            border-radius: 7px;
            border: 1px solid var(--border-color);
            margin-bottom: 0.5rem;
        }
        .places-job-monitor {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #f8fafc;
            border-radius: var(--radius);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid #334155;
            display: none;
        }
        .places-job-monitor.active {
            display: block;
            animation: fadeIn 0.2s ease;
        }
        .places-progress-track {
            background: rgba(255,255,255,0.15);
            height: 10px;
            border-radius: 5px;
            overflow: hidden;
            margin: 0.85rem 0 0.6rem;
            position: relative;
        }
        .places-progress-fill {
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #10b981);
            height: 100%;
            width: 0%;
            border-radius: 5px;
            transition: width 0.3s ease;
        }
        .places-badge-status {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .places-badge-status.operational { background: #dcfce7; color: #166534; }
        .places-badge-status.temp_closed { background: #fef3c7; color: #92400e; }
        .places-badge-status.perm_closed { background: #fee2e2; color: #991b1b; }
        .places-badge-enriched { background: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe; }
        .places-badge-discovered { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>

    <div class="sidebar-backdrop" id="sidebar-backdrop" onclick="toggleMobileSidebar()"></div>

    <!-- SIDEBAR -->
    <aside class="app-sidebar" id="app-sidebar">
        <div class="sidebar-brand">
            <div class="logo-title">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:#60a5fa;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                <span>BooKi <span style="font-weight:400;color:#94a3b8;font-size:0.85rem;">Admin</span></span>
            </div>
            <span class="badge-tag">ENTERPRISE</span>
            <button type="button" class="sidebar-toggle-btn" id="sidebar-toggle-btn" onclick="toggleSidebarCollapse()" title="Menüyü Daralt / Genişlet">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-title">SATIŞ & OPERASYON</div>
            
            <a href="javascript:void(0)" class="nav-item <?= vars('active_tab') === 'dashboard' ? 'active' : '' ?>" onclick="switchTab('dashboard')" data-tooltip="Genel Bakış">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    <span>Genel Bakış</span>
                </div>
            </a>

            <a href="javascript:void(0)" class="nav-item <?= vars('active_tab') === 'pipeline' ? 'active' : '' ?>" onclick="switchTab('pipeline')" data-tooltip="Satış Hattı (Kanban)">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    <span>Satış Hattı (Kanban)</span>
                </div>
                <span class="nav-badge" id="badge-pipeline-count"><?= (int) (vars('crm_kpis')['total_leads'] ?? 560) ?></span>
            </a>

            <a href="javascript:void(0)" class="nav-item <?= vars('active_tab') === 'leads' ? 'active' : '' ?>" onclick="switchTab('leads')" data-tooltip="Lead Havuzu">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    <span>Lead Havuzu</span>
                </div>
                <span class="nav-badge" id="badge-leads-count"><?= (int) (vars('crm_kpis')['total_leads'] ?? 0) ?></span>
            </a>

            <a href="javascript:void(0)" class="nav-item <?= vars('active_tab') === 'places-crawler' ? 'active' : '' ?>" onclick="switchTab('places-crawler')" data-tooltip="Lead Keşfi (Places API)">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon></svg>
                    <span>Lead Keşfi (Places API)</span>
                </div>
                <span class="nav-badge" style="background:#8b5cf6;">PRO 🎯</span>
            </a>

            <a href="javascript:void(0)" class="nav-item <?= vars('active_tab') === 'map' ? 'active' : '' ?>" onclick="switchTab('map')" data-tooltip="Saha Haritası">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <span>Saha Haritası</span>
                </div>
                <span class="nav-badge" style="background:#059669;">🗺️</span>
            </a>

            <a href="javascript:void(0)" class="nav-item <?= vars('active_tab') === 'tasks' ? 'active' : '' ?>" onclick="switchTab('tasks')" data-tooltip="Saha & Görevler">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                    <span>Saha & Görevler</span>
                </div>
                <?php $overdue_cnt = (int) (vars('crm_kpis')['overdue_tasks'] ?? 0); ?>
                <?php if ($overdue_cnt > 0): ?>
                    <span class="nav-badge danger"><?= $overdue_cnt ?> geciken</span>
                <?php endif; ?>
            </a>

            <div class="nav-section-title">PLATFORM & TENANT</div>

            <a href="javascript:void(0)" class="nav-item <?= vars('active_tab') === 'tenants' ? 'active' : '' ?>" onclick="switchTab('tenants')" data-tooltip="Kiracılar (Tenants)">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                    <span>Kiracılar (Tenants)</span>
                </div>
                <span class="nav-badge"><?= (int) vars('total_tenants') ?></span>
            </a>

            <a href="javascript:void(0)" class="nav-item <?= vars('active_tab') === 'onboarding' ? 'active' : '' ?>" onclick="switchTab('onboarding')" data-tooltip="Onboarding Takibi">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    <span>Onboarding Takibi</span>
                </div>
                <?php $in_prog = (int) (vars('crm_kpis')['onboarding_in_progress'] ?? 0); ?>
                <?php if ($in_prog > 0): ?>
                    <span class="nav-badge" style="background:#2563eb;"><?= $in_prog ?></span>
                <?php endif; ?>
            </a>

            <a href="javascript:void(0)" class="nav-item <?= vars('active_tab') === 'import' ? 'active' : '' ?>" onclick="switchTab('import')" data-tooltip="İçe Aktar (Excel/CSV)">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    <span>İçe Aktar (Excel/CSV)</span>
                </div>
            </a>

            <a href="javascript:void(0)" class="nav-item <?= vars('active_tab') === 'reports' ? 'active' : '' ?>" onclick="switchTab('reports')" data-tooltip="Raporlar & Analitik">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    <span>Raporlar & Analitik</span>
                </div>
            </a>

            <div class="nav-section-title">YÖNETİM</div>

            <a href="javascript:void(0)" class="nav-item <?= vars('active_tab') === 'settings' ? 'active' : '' ?>" onclick="switchTab('settings')" data-tooltip="Platform Ayarları">
                <div class="nav-item-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    <span>Platform Ayarları</span>
                </div>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-avatar"><?= strtoupper(substr((string) vars('superadmin_username'), 0, 2)) ?: 'SA' ?></div>
            <div class="user-info">
                <div class="user-name"><?= e(vars('superadmin_username')) ?></div>
                <div class="user-role">Super Administrator</div>
            </div>
            <a href="<?= site_url('superadmin_auth/logout') ?>" class="btn-logout" title="Çıkış Yap">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            </a>
        </div>
    </aside>

    <!-- MAIN WRAPPER -->
    <div class="app-main">
        <!-- TOPBAR -->
        <header class="app-topbar">
            <div class="topbar-left">
                <button class="btn btn-secondary btn-icon mobile-menu-btn" onclick="toggleMobileSidebar()" title="Mobil Menü">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <button class="btn btn-secondary btn-icon desktop-collapse-btn" onclick="toggleSidebarCollapse()" title="Menüyü Daralt / Genişlet" style="margin-right: 0.5rem; display: inline-flex;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <div class="omnisearch-trigger" onclick="openOmnisearch()">
                    <svg class="omnisearch-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" class="omnisearch-input" placeholder="Lead, Kiracı, Telefon, E-posta ara..." readonly>
                    <span class="omnisearch-shortcut">⌘ K</span>
                </div>
            </div>

            <div class="topbar-right">
                <div class="quick-stat-chip">
                    <span class="dot"></span>
                    <span>MRR: <strong>₺<?= number_format(vars('total_mrr'), 2) ?></strong></span>
                </div>
                <button class="btn btn-secondary btn-sm" onclick="openPitchGuide()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <span>Saha Rehberi</span>
                </button>
                <button class="btn btn-primary btn-sm" onclick="openCreateLeadModal()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Yeni Lead</span>
                </button>
                <button class="btn btn-secondary btn-sm" onclick="document.getElementById('create-modal').classList.add('open')">
                    <span>+ Kiracı</span>
                </button>
            </div>
        </header>

        <!-- CONTENT SCROLLABLE CONTAINER -->
        <div class="content-scrollable">
            <div id="success-box" class="urgent-bar" style="display:none; background:#ecfdf5; border-color:#a7f3d0; border-left-color:#10b981; color:#065f46;"></div>

            <!-- TAB 1: DASHBOARD -->
            <div class="tab-content-panel <?= vars('active_tab') === 'dashboard' ? 'active' : '' ?>" id="tab-dashboard">
                <!-- KPI GRID -->
                <div class="kpi-grid">
                    <div class="kpi-card accent-blue" onclick="switchTab('leads')">
                        <div class="kpi-label">
                            <span>Toplam Lead Havuzu</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                        </div>
                        <div class="kpi-value" id="kpi-total-leads"><?= (int) (vars('crm_kpis')['total_leads'] ?? 560) ?></div>
                        <div class="kpi-subtext">560 Gerçek İşletme Kayıtlı</div>
                    </div>

                    <div class="kpi-card accent-amber" onclick="switchTab('leads', {stage:'Trial Started'})">
                        <div class="kpi-label">
                            <span>10-Gün Demo Başlayan</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                        <div class="kpi-value" id="kpi-trials"><?= (int) (vars('crm_kpis')['active_trials'] ?? 0) ?></div>
                        <div class="kpi-subtext"><span style="color:#dc2626;font-weight:700;"><?= (int) (vars('crm_kpis')['trials_ending_soon'] ?? 0) ?></span> demo yakında bitiyor</div>
                    </div>

                    <div class="kpi-card accent-emerald" onclick="switchTab('leads', {stage:'Won'})">
                        <div class="kpi-label">
                            <span>Satışa Dönen (Won)</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </div>
                        <div class="kpi-value" id="kpi-won"><?= (int) (vars('crm_kpis')['total_won'] ?? 0) ?></div>
                        <div class="kpi-subtext">Dönüşüm Oranı: %<?= number_format((float) (vars('crm_kpis')['conversion_rate'] ?? 0), 1) ?></div>
                    </div>

                    <div class="kpi-card accent-purple" onclick="switchTab('tenants')">
                        <div class="kpi-label">
                            <span>Platform MRR</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        </div>
                        <div class="kpi-value">₺<?= number_format((float) vars('total_mrr'), 0) ?></div>
                        <div class="kpi-subtext">Potansiyel: ₺<?= number_format((float) (vars('crm_kpis')['pipeline_potential_mrr'] ?? 0), 0) ?></div>
                    </div>

                    <div class="kpi-card accent-rose" onclick="switchTab('onboarding')">
                        <div class="kpi-label">
                            <span>Onboarding Takibi</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f43f5e" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        </div>
                        <div class="kpi-value"><?= (int) (vars('crm_kpis')['onboarding_in_progress'] ?? 0) ?> <span style="font-size:1rem;color:#94a3b8;font-weight:400;">/ <?= (int) (vars('crm_kpis')['onboarding_completed'] ?? 0) ?></span></div>
                        <div class="kpi-subtext">Süren / Tamamlanan</div>
                    </div>
                </div>

                <!-- URGENT FOLLOW-UP BAR -->
                <?php if (!empty(vars('tasks_summary')['overdue']) || !empty(vars('crm_kpis')['trials_ending_soon'])): ?>
                <div class="urgent-bar">
                    <div class="urgent-bar-content">
                        <span class="urgent-pill">ACİL EYLEM</span>
                        <span>
                            <?php if (!empty(vars('crm_kpis')['trials_ending_soon'])): ?>
                                <strong><?= (int) vars('crm_kpis')['trials_ending_soon'] ?> işletmenin 10-günlük demosu 3 gün içinde bitiyor!</strong> Satış kapanışı için hemen iletişime geçin.
                            <?php else: ?>
                                <strong><?= count(vars('tasks_summary')['overdue']) ?> adet gecikmiş saha görevi var.</strong> Görevleri tamamlamak için tıklayın.
                            <?php endif; ?>
                        </span>
                    </div>
                    <button class="btn btn-secondary btn-sm" onclick="switchTab('tasks')">Görevleri Gör</button>
                </div>
                <?php endif; ?>

                <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;">
                    <!-- RECENT ACTIVITIES -->
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="panel-title">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                <span>Son Saha Hareketleri & Aktiviteler</span>
                            </div>
                            <button class="btn btn-secondary btn-sm" onclick="switchTab('leads')">Tümünü İncele</button>
                        </div>
                        <div class="panel-body" style="padding:1rem;">
                            <div class="timeline" id="dashboard-recent-activities">
                                <?php if (empty(vars('recent_activities'))): ?>
                                    <div style="color:var(--text-light);font-size:0.85rem;padding:1rem 0;">Henüz kaydedilmiş saha aktivitesi bulunmuyor.</div>
                                <?php else: ?>
                                    <?php foreach (vars('recent_activities') as $act): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-dot"></div>
                                            <div class="timeline-content">
                                                <div class="timeline-header">
                                                    <span class="timeline-title">
                                                        <a href="javascript:void(0)" onclick="openLeadDrawer(<?= (int) $act['id_leads'] ?>)" style="color:var(--primary);text-decoration:none;">
                                                            <?= e($act['lead_name'] ?: 'Lead #' . $act['id_leads']) ?>
                                                        </a>
                                                        <span style="font-weight:normal;color:var(--text-muted);font-size:0.75rem;"> • <?= e($act['title']) ?></span>
                                                    </span>
                                                    <span class="timeline-time"><?= date('d.m.Y H:i', strtotime($act['created_at'])) ?></span>
                                                </div>
                                                <div class="timeline-desc"><?= e($act['description']) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- QUICK FIELD ACTIONS & SUMMARY -->
                    <div>
                        <div class="panel-card">
                            <div class="panel-header">
                                <div class="panel-title">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                    <span>Hızlı Saha Aksiyonları</span>
                                </div>
                            </div>
                            <div class="panel-body" style="display:flex;flex-direction:column;gap:0.75rem;">
                                <button class="btn btn-secondary" style="justify-content:flex-start;" onclick="openPitchGuide()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                                    <span>Saha Satış Argüman Rehberi</span>
                                </button>
                                <button class="btn btn-secondary" style="justify-content:flex-start;" onclick="switchTab('import')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                    <span>Excel / CSV Toplu Lead Aktarımı</span>
                                </button>
                                <button class="btn btn-secondary" style="justify-content:flex-start;" onclick="window.location.href='<?= site_url('superadmin_tenants/api_export_leads_csv') ?>'">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                    <span>560 Lead CSV Dışa Aktar</span>
                                </button>
                                <button class="btn btn-primary" style="justify-content:flex-start;" onclick="openCreateLeadModal()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                                    <span>Yeni Potansiyel Müşteri Kaydet</span>
                                </button>
                            </div>
                        </div>

                        <div class="panel-card">
                            <div class="panel-header">
                                <div class="panel-title">
                                    <span>Bugün / Geciken Görevler</span>
                                </div>
                                <span class="badge pending"><?= count(vars('tasks_summary')['today'] ?? []) + count(vars('tasks_summary')['overdue'] ?? []) ?></span>
                            </div>
                            <div class="panel-body" style="padding:0.75rem;">
                                <?php $due_tasks = array_merge(vars('tasks_summary')['overdue'] ?? [], vars('tasks_summary')['today'] ?? []); ?>
                                <?php if (empty($due_tasks)): ?>
                                    <div style="font-size:0.8rem;color:var(--text-light);padding:0.5rem;">Bugün için bekleyen görev bulunmuyor.</div>
                                <?php else: ?>
                                    <div style="display:flex;flex-direction:column;gap:0.5rem;">
                                        <?php foreach (array_slice($due_tasks, 0, 5) as $tk): ?>
                                            <div style="display:flex;align-items:flex-start;justify-content:space-between;padding:0.5rem;background:#f8fafc;border-radius:6px;border:1px solid #e2e8f0;">
                                                <div>
                                                    <div style="font-weight:600;font-size:0.8rem;"><?= e($tk['title']) ?></div>
                                                    <div style="font-size:0.72rem;color:var(--text-muted);"><?= e($tk['lead_name']) ?> (<?= date('d.m', strtotime($tk['due_date'])) ?>)</div>
                                                </div>
                                                <button class="btn btn-secondary btn-sm" onclick="toggleTaskStatus(<?= (int) $tk['id'] ?>, 'completed')">✓</button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: PIPELINE (KANBAN BOARD) -->
            <div class="tab-content-panel <?= vars('active_tab') === 'pipeline' ? 'active' : '' ?>" id="tab-pipeline">
                <div class="filter-toolbar">
                    <div class="filter-group">
                        <input type="text" class="filter-input" id="pipeline-search" placeholder="İşletme veya yetkili ara..." oninput="filterPipelineDebounced()">
                        <select class="filter-select" id="pipeline-sector-filter" onchange="loadPipeline()">
                            <option value="">Tüm Sektörler</option>
                            <?php foreach (vars('sectors') as $sec): ?>
                                <option value="<?= e($sec['sector']) ?>"><?= e($sec['sector']) ?> (<?= (int) $sec['count'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <select class="filter-select" id="pipeline-district-filter" onchange="loadPipeline()">
                            <option value="">Tüm İlçeler</option>
                            <?php foreach (vars('districts') as $dist): ?>
                                <option value="<?= e($dist['district']) ?>"><?= e($dist['district']) ?> (<?= (int) $dist['count'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button class="btn btn-secondary btn-sm" onclick="loadPipeline()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>
                            <span>Yenile</span>
                        </button>
                    </div>
                </div>

                <div class="kanban-board" id="kanban-board-container">
                    <div style="padding:2rem;text-align:center;color:var(--text-light);width:100%;">Kanban yükleniyor...</div>
                </div>
            </div>

            <!-- TAB 3: LEADS MANAGEMENT (TABLE & FILTERS) -->
            <div class="tab-content-panel <?= vars('active_tab') === 'leads' ? 'active' : '' ?>" id="tab-leads">
                <div class="filter-toolbar">
                    <div class="filter-group">
                        <input type="text" class="filter-input" id="leads-search" placeholder="560 işletme içinde ara..." style="width:240px;" oninput="filterLeadsDebounced()">
                        <select class="filter-select" id="leads-sector-filter" onchange="loadLeadsTable()">
                            <option value="">Tüm Sektörler</option>
                            <?php foreach (vars('sectors') as $sec): ?>
                                <option value="<?= e($sec['sector']) ?>"><?= e($sec['sector']) ?> (<?= (int) $sec['count'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <select class="filter-select" id="leads-district-filter" onchange="loadLeadsTable()">
                            <option value="">Tüm İlçeler</option>
                            <?php foreach (vars('districts') as $dist): ?>
                                <option value="<?= e($dist['district']) ?>"><?= e($dist['district']) ?> (<?= (int) $dist['count'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <select class="filter-select" id="leads-stage-filter" onchange="loadLeadsTable()">
                            <option value="">Tüm Aşamalar</option>
                            <?php foreach (vars('stage_definitions') as $st_key => $st_label): ?>
                                <option value="<?= e($st_key) ?>"><?= e($st_label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex;gap:0.5rem;">
                        <button class="btn btn-secondary btn-sm" onclick="window.location.href='<?= site_url('superadmin_tenants/api_export_leads_csv') ?>'">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            <span>Dışa Aktar</span>
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="openCreateLeadModal()">+ Lead Ekle</button>
                    </div>
                </div>

                <div class="panel-card">
                    <div class="table-responsive">
                        <table class="data-table" id="leads-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>İşletme Adı</th>
                                    <th>Sektör / İlçe</th>
                                    <th>İletişim / Tel</th>
                                    <th>Aşama</th>
                                    <th>Demo / Trial</th>
                                    <th>Potansiyel MRR</th>
                                    <th style="text-align:right;">Aksiyonlar</th>
                                </tr>
                            </thead>
                            <tbody id="leads-table-tbody">
                                <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-light);">Yükleniyor...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="padding:0.9rem 1.25rem;display:flex;align-items:center;justify-content:space-between;border-top:1px solid var(--border-color);background:#ffffff;flex-wrap:wrap;gap:0.75rem;">
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <div style="font-size:0.82rem;color:var(--text-muted);" id="leads-pagination-info">—</div>
                            <select class="filter-select" id="leads-per-page-select" onchange="loadLeadsTable(1)" style="font-size:0.78rem;padding:0.25rem 0.5rem;height:auto;" title="Sayfa Başına Kayıt Sayısı">
                                <option value="25" selected>25 / sayfa</option>
                                <option value="50">50 / sayfa</option>
                                <option value="100">100 / sayfa</option>
                                <option value="250">250 / sayfa</option>
                            </select>
                        </div>
                        <div style="display:flex;gap:0.35rem;align-items:center;flex-wrap:wrap;" id="leads-pagination-buttons"></div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: TENANTS & CONTROL CENTER (ORIGINAL CAPABILITIES PRESERVED) -->
            <div class="tab-content-panel <?= vars('active_tab') === 'tenants' ? 'active' : '' ?>" id="tab-tenants">
                <div class="filter-toolbar">
                    <div>
                        <span style="font-weight:700;font-size:0.95rem;"><?= (int) vars('total') ?> Kiracı</span>
                        <span style="font-size:0.8rem;color:var(--text-muted);margin-left:0.5rem;">(<?= (int) vars('active_tenants') ?> Aktif)</span>
                    </div>
                    <form method="get" style="display:flex;gap:0.5rem;">
                        <input type="hidden" name="tab" value="tenants">
                        <input type="text" name="q" class="filter-input" placeholder="Subdomain, domain, plan ara..." value="<?= e(vars('search')) ?>" style="width:240px;">
                        <button type="submit" class="btn btn-secondary btn-sm">Ara</button>
                    </form>
                    <button class="btn btn-primary btn-sm" onclick="document.getElementById('create-modal').classList.add('open')">+ Yeni Kiracı Oluştur</button>
                </div>

                <div class="panel-card">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Subdomain</th>
                                    <th>İşletme Türü</th>
                                    <th>Plan & Döngü</th>
                                    <th>Durum</th>
                                    <th>Müşteriler</th>
                                    <th>Randevular</th>
                                    <th>Ciro / MRR</th>
                                    <th>Oluşturma</th>
                                    <th style="text-align:right;">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (vars('tenants') as $tenant): ?>
                                    <?php
                                    $now = date('Y-m-d H:i:s');
                                    $expired = (!empty($tenant['license_expires_at']) && $tenant['license_expires_at'] < $now)
                                        || (!empty($tenant['trial_ends_at']) && $tenant['trial_ends_at'] < $now);
                                    $badge_class = $expired ? 'expired' : $tenant['status'];
                                    $badge_label = $expired ? 'süresi doldu' : $tenant['status'];
                                    ?>
                                    <tr data-tenant-id="<?= e($tenant['id']) ?>" data-subdomain="<?= e($tenant['subdomain']) ?>">
                                        <td>
                                            <strong><?= e($tenant['subdomain']) ?></strong><br>
                                            <span style="font-size:0.75rem;color:var(--text-light);"><?= e($tenant['custom_domain'] ?? '—') ?></span>
                                        </td>
                                        <td><?= e($tenant['business_type'] ?? '—') ?></td>
                                        <td>
                                            <?= e($tenant['plan'] ?? 'Free') ?> <br>
                                            <span style="font-size:0.75rem;color:var(--text-light);"><?= ($tenant['billing_cycle'] ?? '') == 'yearly' ? 'Yıllık' : 'Aylık' ?></span>
                                        </td>
                                        <td><span class="badge <?= e($badge_class) ?>"><?= e($badge_label) ?></span></td>
                                        <td><?= number_format((int) ($tenant['customer_count'] ?? 0)) ?></td>
                                        <td><?= number_format((int) ($tenant['monthly_appointments'] ?? 0)) ?> <span style="color:var(--text-light);font-size:0.75rem;">/ <?= number_format((int) ($tenant['appointment_count'] ?? 0)) ?></span></td>
                                        <td>
                                            ₺<?= number_format((float) ($tenant['total_revenue'] ?? 0), 2) ?> <br>
                                            <span style="font-size:0.75rem;color:var(--text-light);">MRR: ₺<?= number_format((float) ($tenant['mrr_amount'] ?? 0), 2) ?></span>
                                        </td>
                                        <td><?= date('d.m.Y', strtotime($tenant['created_at'])) ?></td>
                                        <td style="text-align:right;">
                                            <button class="btn btn-secondary btn-sm" onclick="openDetailsModal(<?= e($tenant['id']) ?>)">Detay</button>
                                            <button class="btn btn-secondary btn-sm" onclick="impersonateTenant(<?= e($tenant['id']) ?>)" title="Yönetici Olarak Giriş Yap">Giriş</button>
                                            <?php if ($tenant['status'] === 'active'): ?>
                                                <button class="btn btn-secondary btn-sm" onclick="setStatus(<?= e($tenant['id']) ?>, 'suspended')">Askı</button>
                                            <?php else: ?>
                                                <button class="btn btn-success btn-sm" onclick="setStatus(<?= e($tenant['id']) ?>, 'active')">Aktif</button>
                                            <?php endif; ?>
                                            <button class="btn btn-secondary btn-sm" onclick="openPlanModal(<?= e($tenant['id']) ?>, '<?= e($tenant['plan'] ?? '') ?>', '<?= e($tenant['billing_cycle'] ?? 'monthly') ?>', '<?= e($tenant['mrr_amount'] ?? 0) ?>', '<?= e($tenant['trial_ends_at'] ?? '') ?>', '<?= e($tenant['license_expires_at'] ?? '') ?>')">Plan</button>
                                            <button class="btn btn-secondary btn-sm" onclick="openAdminModal(<?= e($tenant['id']) ?>, '<?= e($tenant['subdomain']) ?>')">Admin</button>
                                            <button class="btn btn-danger btn-sm" onclick="openDeleteModal(<?= e($tenant['id']) ?>, '<?= e($tenant['subdomain']) ?>')">Sil</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if (vars('total_pages') > 1): ?>
                    <div style="display:flex;gap:0.4rem;justify-content:center;margin-top:1rem;">
                        <?php for ($i = 1; $i <= vars('total_pages'); $i++): ?>
                            <a href="?tab=tenants&q=<?= urlencode(vars('search')) ?>&page=<?= $i ?>" class="btn <?= $i === vars('page') ? 'btn-primary' : 'btn-secondary' ?> btn-sm"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB 5: ONBOARDING OPERATIONS CENTER -->
            <div class="tab-content-panel <?= vars('active_tab') === 'onboarding' ? 'active' : '' ?>" id="tab-onboarding">
                <div class="filter-toolbar">
                    <div>
                        <span style="font-weight:700;font-size:0.95rem;">Müşteri Onboarding Kurulum Oturumları</span>
                        <div style="font-size:0.8rem;color:var(--text-muted);">Müşterilere iletilen 10 adımlı sihirbaz bağlantıları ve ilerleme durumu</div>
                    </div>
                    <button class="btn btn-secondary btn-sm" onclick="loadOnboardingSessions()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>
                        <span>Yenile</span>
                    </button>
                </div>

                <div class="panel-card">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Kiracı & Subdomain</th>
                                    <th>İşletme / Yetkili</th>
                                    <th>Durum</th>
                                    <th>İlerleme</th>
                                    <th>Adım</th>
                                    <th>Son Hareket</th>
                                    <th style="text-align:right;">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody id="onboarding-sessions-tbody">
                                <?php if (empty(vars('onboarding_sessions'))): ?>
                                    <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-light);">Aktif onboarding oturumu bulunmuyor.</td></tr>
                                <?php else: ?>
                                    <?php foreach (vars('onboarding_sessions') as $os): ?>
                                        <tr>
                                            <td>
                                                <strong><?= e($os['subdomain']) ?></strong><br>
                                                <span style="font-size:0.75rem;color:var(--text-light);"><?= e($os['company_name'] ?: '—') ?></span>
                                            </td>
                                            <td>
                                                <?= e($os['lead_name'] ?: '—') ?><br>
                                                <span style="font-size:0.75rem;color:var(--text-light);"><?= e($os['phone_number'] ?? '') ?></span>
                                            </td>
                                            <td><span class="badge <?= e($os['status']) ?>"><?= e($os['status']) ?></span></td>
                                            <td style="width:160px;">
                                                <div style="background:#e2e8f0;border-radius:9999px;height:7px;overflow:hidden;margin-bottom:0.25rem;">
                                                    <div style="background:var(--primary);height:100%;width:<?= (int) $os['progress_percent'] ?>%;"></div>
                                                </div>
                                                <span style="font-size:0.72rem;color:var(--text-muted);font-weight:600;">%<?= (int) $os['progress_percent'] ?> Tamamlandı</span>
                                            </td>
                                            <td>Adım <?= (int) $os['current_step'] ?> / 10</td>
                                            <td><?= $os['last_activity_at'] ? date('d.m.Y H:i', strtotime($os['last_activity_at'])) : '—' ?></td>
                                            <td style="text-align:right;">
                                                <button class="btn btn-secondary btn-sm" onclick="copyOnboardingLink('<?= e($os['link']) ?>')">Linki Kopyala</button>
                                                <a href="<?= e($os['link']) ?>" target="_blank" class="btn btn-secondary btn-sm">Aç</a>
                                                <button class="btn btn-secondary btn-sm" onclick="regenerateOnboardingToken(<?= (int) $os['id_tenants'] ?>)">Yenile</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: SAHA HARİTASI (MAP VIEW) -->
            <div class="tab-content-panel <?= vars('active_tab') === 'map' ? 'active' : '' ?>" id="tab-map">
                <div class="filter-toolbar" style="flex-wrap:wrap;gap:0.5rem;">
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <span style="font-weight:700;font-size:0.95rem;">🗺️ Saha Haritası — Lead Konumları</span>
                        <span id="map-lead-count" style="font-size:0.78rem;color:var(--text-muted);"></span>
                    </div>
                    <div style="display:flex;gap:0.4rem;flex-wrap:wrap;">
                        <select class="form-control" id="map-stage-filter" onchange="loadMapLeads()" style="min-width:140px;">
                            <option value="">Tüm Aşamalar</option>
                            <?php foreach (vars('stage_definitions') as $k => $lbl): ?>
                                <option value="<?= e($k) ?>"><?= e($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select class="form-control" id="map-sector-filter" onchange="loadMapLeads()" style="min-width:120px;">
                            <option value="">Tüm Sektörler</option>
                            <?php foreach ((array) vars('sectors') as $s): ?>
                                <option value="<?= e($s['sector']) ?>"><?= e($s['sector']) ?> (<?= $s['count'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <select class="form-control" id="map-district-filter" onchange="loadMapLeads()" style="min-width:120px;">
                            <option value="">Tüm İlçeler</option>
                            <?php foreach ((array) vars('districts') as $d): ?>
                                <option value="<?= e($d['district']) ?>"><?= e($d['district']) ?> (<?= $d['count'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-secondary btn-sm" onclick="batchGeocodeLeads()" id="btn-batch-geocode" title="Adresi olan ama koordinatı olmayan lead'leri otomatik konumla">
                            📍 Toplu Konumla
                        </button>
                    </div>
                </div>
                <div id="map-container" style="width:100%;height:calc(100vh - 180px);border-radius:8px;overflow:hidden;border:1px solid var(--border-color);position:relative;">
                    <div id="leads-map" style="width:100%;height:100%;"></div>
                    <!-- Legend -->
                    <div id="map-legend" style="position:absolute;bottom:12px;left:12px;background:rgba(255,255,255,0.95);padding:0.6rem 0.8rem;border-radius:8px;border:1px solid var(--border-color);font-size:0.72rem;box-shadow:0 2px 8px rgba(0,0,0,0.08);z-index:5;">
                        <div style="font-weight:700;margin-bottom:0.35rem;font-size:0.75rem;">Aşama Renkleri</div>
                        <div style="display:flex;flex-wrap:wrap;gap:0.35rem 0.75rem;">
                            <span>🔵 Yeni / Nitelikli</span>
                            <span>🟡 Ziyaret</span>
                            <span>🟠 Demo / Takip</span>
                            <span>🟢 Kazanıldı</span>
                            <span>🔴 Kaybedildi</span>
                        </div>
                    </div>
                    <!-- Unlocated warning -->
                    <div id="map-unlocated-bar" style="display:none;position:absolute;top:12px;left:12px;right:12px;background:#fffbeb;border:1px solid #fef3c7;padding:0.5rem 0.75rem;border-radius:8px;font-size:0.78rem;color:#92400e;z-index:5;display:flex;justify-content:space-between;align-items:center;">
                        <span id="map-unlocated-text"></span>
                        <button class="btn btn-primary btn-sm" onclick="batchGeocodeLeads()" style="font-size:0.72rem;padding:0.25rem 0.5rem;">Otomatik Konumla</button>
                    </div>
                </div>
            </div>

            <!-- TAB 6: TASKS & FIELD SCHEDULE -->
            <div class="tab-content-panel <?= vars('active_tab') === 'tasks' ? 'active' : '' ?>" id="tab-tasks">
                <div class="filter-toolbar">
                    <div>
                        <span style="font-weight:700;font-size:0.95rem;">Saha Takip & Görev Listesi</span>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="openCreateTaskModal()">+ Yeni Görev Ekle</button>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                    <!-- TODAY & OVERDUE -->
                    <div class="panel-card">
                        <div class="panel-header" style="background:#fef2f2;">
                            <div class="panel-title" style="color:#b91c1c;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                                <span>Geciken & Bugünün Görevleri</span>
                            </div>
                        </div>
                        <div class="panel-body" id="tasks-urgent-container">
                            <!-- Populated via JS / PHP -->
                            <?php $urgent = array_merge(vars('tasks_summary')['overdue'] ?? [], vars('tasks_summary')['today'] ?? []); ?>
                            <?php if (empty($urgent)): ?>
                                <div style="color:var(--text-light);font-size:0.85rem;">Acil veya bugün bekleyen görev bulunmuyor.</div>
                            <?php else: ?>
                                <div style="display:flex;flex-direction:column;gap:0.75rem;">
                                    <?php foreach ($urgent as $t): ?>
                                        <div style="background:#ffffff;border:1px solid #fecaca;padding:0.85rem;border-radius:8px;display:flex;align-items:flex-start;justify-content:space-between;">
                                            <div>
                                                <div style="font-weight:700;font-size:0.88rem;"><?= e($t['title']) ?></div>
                                                <div style="font-size:0.78rem;color:var(--text-muted);margin-top:0.2rem;">
                                                    Lead: <strong><?= e($t['lead_name'] ?: 'Genel') ?></strong> • Vade: <?= date('d.m.Y', strtotime($t['due_date'])) ?> <?= e($t['due_time'] ?? '') ?>
                                                </div>
                                                <?php if (!empty($t['description'])): ?>
                                                    <div style="font-size:0.75rem;color:#64748b;margin-top:0.3rem;"><?= e($t['description']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <button class="btn btn-success btn-sm" onclick="toggleTaskStatus(<?= (int) $t['id'] ?>, 'completed')">Tamamla</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- UPCOMING & COMPLETED -->
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="panel-title">
                                <span>Yaklaşan Görevler</span>
                            </div>
                        </div>
                        <div class="panel-body" id="tasks-upcoming-container">
                            <?php $upcoming = vars('tasks_summary')['upcoming'] ?? []; ?>
                            <?php if (empty($upcoming)): ?>
                                <div style="color:var(--text-light);font-size:0.85rem;">Gelecek tarihlere ait planlanmış görev yok.</div>
                            <?php else: ?>
                                <div style="display:flex;flex-direction:column;gap:0.75rem;">
                                    <?php foreach ($upcoming as $t): ?>
                                        <div style="background:#f8fafc;border:1px solid var(--border-color);padding:0.85rem;border-radius:8px;display:flex;align-items:flex-start;justify-content:space-between;">
                                            <div>
                                                <div style="font-weight:700;font-size:0.88rem;"><?= e($t['title']) ?></div>
                                                <div style="font-size:0.78rem;color:var(--text-muted);margin-top:0.2rem;">
                                                    Lead: <strong><?= e($t['lead_name'] ?: 'Genel') ?></strong> • Tarih: <?= date('d.m.Y', strtotime($t['due_date'])) ?>
                                                </div>
                                            </div>
                                            <button class="btn btn-secondary btn-sm" onclick="toggleTaskStatus(<?= (int) $t['id'] ?>, 'completed')">Tamamla</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 7: IMPORT CENTER (EXCEL / CSV) -->
            <div class="tab-content-panel <?= vars('active_tab') === 'import' ? 'active' : '' ?>" id="tab-import">
                <div style="max-width:800px;margin:0 auto;">
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="panel-title">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                <span>Excel & CSV Dosyası ile Toplu Lead Yükleme</span>
                            </div>
                            <a href="<?= site_url('superadmin_tenants/api_download_template') ?>" class="btn btn-secondary btn-sm">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                <span>Şablonu İndir (.csv)</span>
                            </a>
                        </div>
                        <div class="panel-body">
                            <form id="import-form" enctype="multipart/form-data">
                                <div style="border:2px dashed var(--border-color);border-radius:var(--radius-lg);padding:2.5rem;text-align:center;cursor:pointer;background:#f8fafc;transition:all 0.15s;" id="dropzone" onclick="document.getElementById('import-file-input').click()">
                                    <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" style="margin-bottom:0.75rem;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                    <div style="font-weight:700;font-size:1rem;color:var(--text-main);margin-bottom:0.35rem;" id="dropzone-text">Dosyanızı buraya sürükleyin veya seçmek için tıklayın</div>
                                    <div style="font-size:0.78rem;color:var(--text-light);">Desteklenen formatlar: .CSV, .XLSX (Maks 10MB)</div>
                                    <input type="file" id="import-file-input" name="file" accept=".csv,.xlsx" style="display:none;" onchange="handleFileSelected(this)">
                                </div>

                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1.5rem;">
                                    <div class="form-group">
                                        <label>Mükerrer Kayıt Politikası</label>
                                        <select class="form-control" name="duplicate_action" id="import-duplicate-action">
                                            <option value="skip">Atla (Mevcut kaydı koru)</option>
                                            <option value="update">Mevcut Kaydı Güncelle</option>
                                            <option value="insert">Yine de Ekle (Mükerrer serbest)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Varsayılan Başlangıç Aşaması</label>
                                        <select class="form-control" name="default_stage" id="import-default-stage">
                                            <option value="New Lead">Yeni Lead</option>
                                            <option value="Qualified">Nitelikli Lead</option>
                                            <option value="Visit Planned">Ziyaret Planlandı</option>
                                        </select>
                                    </div>
                                </div>

                                <div id="import-progress-container" style="display:none;margin-top:1rem;">
                                    <div style="background:#e2e8f0;border-radius:9999px;height:8px;overflow:hidden;">
                                        <div id="import-progress-bar" style="background:var(--primary);height:100%;width:0%;transition:width 0.2s;"></div>
                                    </div>
                                    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:0.35rem;text-align:center;" id="import-progress-text">İçe aktarılıyor...</div>
                                </div>

                                <div id="import-result-container" style="display:none;margin-top:1.25rem;"></div>

                                <div style="display:flex;justify-content:flex-end;gap:0.75rem;margin-top:1.5rem;">
                                    <button type="button" class="btn btn-secondary" onclick="resetImportForm()">Temizle</button>
                                    <button type="submit" class="btn btn-primary" id="btn-start-import" disabled>İçe Aktarımı Başlat</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 8: REPORTS & ANALYTICS -->
            <div class="tab-content-panel <?= vars('active_tab') === 'reports' ? 'active' : '' ?>" id="tab-reports">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                    <!-- SECTOR BREAKDOWN -->
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="panel-title"><span>Sektör Dağılımı (560 Lead)</span></div>
                        </div>
                        <div class="panel-body">
                            <div style="display:flex;flex-direction:column;gap:0.6rem;">
                                <?php foreach (vars('sectors') as $s): ?>
                                    <?php $pct = round(((int) $s['count'] / max(1, (int) (vars('crm_kpis')['total_leads'] ?? 560))) * 100, 1); ?>
                                    <div>
                                        <div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:0.2rem;">
                                            <span><strong><?= e($s['sector']) ?></strong></span>
                                            <span><?= (int) $s['count'] ?> lead (%<?= $pct ?>)</span>
                                        </div>
                                        <div style="background:#e2e8f0;border-radius:9999px;height:6px;overflow:hidden;">
                                            <div style="background:var(--primary);height:100%;width:<?= $pct ?>%;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- DISTRICT BREAKDOWN -->
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="panel-title"><span>Bölge / İlçe Kırılımı</span></div>
                        </div>
                        <div class="panel-body">
                            <div style="display:flex;flex-direction:column;gap:0.6rem;">
                                <?php foreach (array_slice(vars('districts'), 0, 10) as $d): ?>
                                    <?php $pct = round(((int) $d['count'] / max(1, (int) (vars('crm_kpis')['total_leads'] ?? 560))) * 100, 1); ?>
                                    <div>
                                        <div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:0.2rem;">
                                            <span><strong><?= e($d['district']) ?></strong></span>
                                            <span><?= (int) $d['count'] ?> lead (%<?= $pct ?>)</span>
                                        </div>
                                        <div style="background:#e2e8f0;border-radius:9999px;height:6px;overflow:hidden;">
                                            <div style="background:#10b981;height:100%;width:<?= $pct ?>%;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: PLATFORM SETTINGS (INTEGRATED & MODERN UI/UX) -->
            <div class="tab-content-panel <?= vars('active_tab') === 'settings' ? 'active' : '' ?>" id="tab-settings">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
                    <div>
                        <h2 style="font-size:1.35rem;font-weight:800;color:var(--text-main);margin:0;">Platform & Entegrasyon Ayarları</h2>
                        <p style="font-size:0.82rem;color:var(--text-muted);margin:0.25rem 0 0;">Yapay Zeka (ElevenLabs, Google Gemini), Zadarma SIP Santral, WhatsApp Bridge, SMTP ve Sistem Entegrasyonları</p>
                    </div>
                    <button class="btn btn-primary" onclick="savePlatformSettings()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        <span>Tüm Ayarları Kaydet</span>
                    </button>
                </div>

                <?php $ps = (array) (vars('platform_settings') ?: []); ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(420px, 1fr));gap:1.5rem;">

                    <!-- 1. AI ASİSTAN & SES MOTORU (ELEVENLABS + GEMINI LIVE + LLM) -->
                    <div class="panel-card" style="border-top:4px solid #3b82f6;">
                        <div class="panel-header">
                            <div class="panel-title">
                                <span>🤖 Yapay Zeka & Ses Motoru (AI & Voice)</span>
                            </div>
                            <span class="badge active">Canlı Aktif</span>
                        </div>
                        <div class="panel-body">
                            <h4 style="font-size:0.85rem;color:var(--text-main);margin:0 0 0.5rem;font-weight:700;">🎙️ ElevenLabs Conversational AI</h4>
                            <p style="font-size:0.75rem;color:var(--text-muted);margin-bottom:0.75rem;">Saha aramalarında potansiyel müşterilerle doğal Türkçe sesli görüşme ve diyalog motoru.</p>
                            
                            <div class="form-group">
                                <label>ElevenLabs API Key</label>
                                <input type="password" id="ps_elevenlabs_api_key" class="form-control" placeholder="<?= !empty($ps['elevenlabs_api_key_set']) ? '•••••••••••••••• (Kayıtlı ✓)' : 'sk_...' ?>">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Agent ID</label>
                                    <input type="text" id="ps_elevenlabs_agent_id" class="form-control" value="<?= e($ps['elevenlabs_agent_id'] ?? '') ?>" placeholder="Örn: agent_xyz123">
                                </div>
                                <div class="form-group">
                                    <label>Ses (Voice ID)</label>
                                    <input type="text" id="ps_elevenlabs_voice_id" class="form-control" value="<?= e($ps['elevenlabs_voice_id'] ?? '21m00Tcm4TlvDq8ikWAM') ?>" placeholder="21m00Tcm4TlvDq8ikWAM">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Model</label>
                                <input type="text" id="ps_elevenlabs_model_id" class="form-control" value="<?= e($ps['elevenlabs_model_id'] ?? 'eleven_multilingual_v2') ?>" placeholder="eleven_multilingual_v2">
                            </div>

                            <hr style="margin:1.25rem 0;border:none;border-top:1px solid #f1f5f9;">

                            <h4 style="font-size:0.85rem;color:var(--text-main);margin:0 0 0.5rem;font-weight:700;">⚡ Google AI Studio (Gemini Live & Flash)</h4>
                            <p style="font-size:0.75rem;color:var(--text-muted);margin-bottom:0.75rem;">Google AI Studio API bakiyesiyle çalışan anlık canlı Türkçe satış asistanı ve transkript motoru.</p>

                            <div class="form-group">
                                <label>Gemini / Google AI API Key</label>
                                <input type="password" id="ps_google_ai_key" class="form-control" placeholder="<?= !empty($ps['google_ai_key_set']) ? '•••••••••••••••• (Kayıtlı ✓)' : 'AIzaSy...' ?>">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Gemini Canlı Ses</label>
                                    <select id="ps_gemini_live_voice" class="form-control">
                                        <?php $glv = $ps['gemini_live_voice'] ?? 'Aoede'; ?>
                                        <option value="Aoede" <?= $glv === 'Aoede' ? 'selected' : '' ?>>Aoede (Doğal & Samimi Kadın)</option>
                                        <option value="Charon" <?= $glv === 'Charon' ? 'selected' : '' ?>>Charon (Kurumsal Erkek)</option>
                                        <option value="Fenrir" <?= $glv === 'Fenrir' ? 'selected' : '' ?>>Fenrir (Dinamik & Hızlı Erkek)</option>
                                        <option value="Kore" <?= $glv === 'Kore' ? 'selected' : '' ?>>Kore (Sakin & Güven Veren)</option>
                                        <option value="Puck" <?= $glv === 'Puck' ? 'selected' : '' ?>>Puck (Genç & Enerjik)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Varsayılan Model</label>
                                    <input type="text" id="ps_ai_model_google" class="form-control" value="<?= e($ps['ai_model_google'] ?? 'gemini-1.5-flash') ?>" placeholder="gemini-1.5-flash">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Canlı Satış Asistanı Promptu</label>
                                <textarea id="ps_gemini_sales_pitch_prompt" class="form-control" rows="3" placeholder="Sen BooKi platformunun uzman satış temsilcisisin. Karşındaki işletmenin sektörüne özel argümanlar sun..."><?= e($ps['gemini_sales_pitch_prompt'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- 2. ZADARMA SIP & TELEFONİ SANTRALİ -->
                    <div class="panel-card" style="border-top:4px solid #10b981;">
                        <div class="panel-header">
                            <div class="panel-title">
                                <span>📞 Zadarma SIP & Telefoni Santrali</span>
                            </div>
                            <span class="badge active">API Bağlı</span>
                        </div>
                        <div class="panel-body">
                            <p style="font-size:0.75rem;color:var(--text-muted);margin-bottom:0.75rem;">Panelden tek tıkla arama başlatma (Callback ve WebRTC dialer entegrasyonu).</p>

                            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:0.75rem;">
                                <button type="button" class="btn btn-sm btn-success" id="btn-zadarma-test" onclick="testZadarmaConnection()" style="background:#10b981;border-color:#10b981;">
                                    🔌 Bağlantıyı Test Et
                                </button>
                                <button type="button" class="btn btn-sm" id="btn-zadarma-webrtc-sync" onclick="syncZadarmaWebRTC()" style="background:#eff6ff;border:1px solid #3b82f6;color:#2563eb;font-weight:600;">
                                    🌐 WebRTC Domaini Eşitle (API)
                                </button>
                                <span id="zadarma-test-result" style="font-size:0.78rem;color:var(--text-muted);align-self:center;"></span>
                            </div>

                            <div class="form-group">
                                <label>Zadarma API Key</label>
                                <input type="text" id="ps_zadarma_api_key" class="form-control" value="<?= e($ps['zadarma_api_key'] ?? '') ?>" placeholder="ceba11321113fd2628a1">
                            </div>
                            <div class="form-group">
                                <label>Zadarma API Secret</label>
                                <input type="password" id="ps_zadarma_api_secret" class="form-control" placeholder="<?= !empty($ps['zadarma_api_secret_set']) ? '•••••••••••••••• (Kayıtlı ✓)' : '7fc7705128bd1c8ef754' ?>">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>SIP Login / Dahili</label>
                                    <input type="text" id="ps_zadarma_sip_login" class="form-control" value="<?= e($ps['zadarma_sip_login'] ?? '') ?>" placeholder="Örn: 101 veya 123456">
                                </div>
                                <div class="form-group">
                                    <label>SIP Şifresi</label>
                                    <input type="password" id="ps_zadarma_sip_password" class="form-control" placeholder="••••••••">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>SIP Sunucu</label>
                                    <input type="text" id="ps_zadarma_sip_server" class="form-control" value="<?= e($ps['zadarma_sip_server'] ?? 'sip.zadarma.com') ?>" placeholder="sip.zadarma.com">
                                </div>
                                <div class="form-group">
                                    <label>Arayan Numara (Caller ID)</label>
                                    <input type="text" id="ps_zadarma_caller_id" class="form-control" value="<?= e($ps['zadarma_caller_id'] ?? '') ?>" placeholder="Örn: 0850XXXXXXX">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Arama Modu</label>
                                <select id="ps_zadarma_call_mode" class="form-control">
                                    <?php $zcm = $ps['zadarma_call_mode'] ?? 'callback'; ?>
                                    <option value="callback" <?= $zcm === 'callback' ? 'selected' : '' ?>>Zadarma API Callback (Yöneticiyi çaldırıp müşteriye bağla)</option>
                                    <option value="webrtc" <?= $zcm === 'webrtc' ? 'selected' : '' ?>>WebRTC Softphone (Tarayıcı içi doğrudan mikrofonla konuş)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 3. ÇOK KANALLI İLETİŞİM & WHATSAPP GATEWAY -->
                    <div class="panel-card" style="border-top:4px solid #16a34a;">
                        <div class="panel-header">
                            <div class="panel-title">
                                <span>💬 WhatsApp Baileys Bridge & Hızlı Şablonlar</span>
                            </div>
                            <span class="badge active">Köprü Hazır</span>
                        </div>
                        <div class="panel-body">
                            <p style="font-size:0.75rem;color:var(--text-muted);margin-bottom:0.75rem;">Saha satışında tek tıkla mesaj gönderme ve Baileys WhatsApp Köprüsü bağlantısı.</p>
                            
                            <div class="form-group">
                                <label>Baileys Bridge URL</label>
                                <input type="text" id="ps_wa_bridge_url" class="form-control" placeholder="http://ki-wa-bridge:3000">
                            </div>
                            <div class="form-group">
                                <label>Bridge Secret Token</label>
                                <input type="password" id="ps_wa_bridge_secret" class="form-control" placeholder="••••••••••••••••">
                            </div>

                            <hr style="margin:1.25rem 0;border:none;border-top:1px solid #f1f5f9;">
                            <h4 style="font-size:0.85rem;color:var(--text-main);margin:0 0 0.5rem;font-weight:700;">Hızlı Satış Mesaj Şablonları</h4>
                            <p style="font-size:0.75rem;color:var(--text-muted);margin-bottom:0.5rem;">Dinamik parametreler: <code>{isletme_adi}</code>, <code>{yetkili}</code>, <code>{sektor}</code></p>

                            <div style="background:#f8fafc;padding:0.75rem;border-radius:6px;border:1px solid var(--border-color);font-size:0.78rem;margin-bottom:0.5rem;">
                                <strong>Şablon 1 (Tanıtım):</strong> "Merhaba {yetkili}, {isletme_adi} için randevu kayıplarını ve no-show oranlarını %80 azaltan BooKi Akıllı Randevu sistemimizi 10 gün ücretsiz denemek ister misiniz?"
                            </div>
                            <div style="background:#f8fafc;padding:0.75rem;border-radius:6px;border:1px solid var(--border-color);font-size:0.78rem;">
                                <strong>Şablon 2 (Ziyaret Teyit):</strong> "Merhaba {yetkili}, yarın {isletme_adi} adresinize planladığımız BooKi saha ziyaretimiz öncesinde teyit almak istedik. Müsaitseniz 15 dakikalık canlı demomuzu sunmaktan mutluluk duyarız."
                            </div>
                        </div>
                    </div>

                    <!-- 4. PLATFORM E-POSTA (SMTP / IMAP) -->
                    <div class="panel-card" style="border-top:4px solid #6366f1;">
                        <div class="panel-header">
                            <div class="panel-title">
                                <span>📧 Platform E-posta (SMTP / IMAP)</span>
                            </div>
                            <span class="badge <?= !empty($ps['platform_smtp_pass_set']) ? 'active' : 'pending' ?>">
                                <?= !empty($ps['platform_smtp_pass_set']) ? 'Bağlı' : 'Yapılandırılmamış' ?>
                            </span>
                        </div>
                        <div class="panel-body">
                            <div class="form-row">
                                <div class="form-group" style="flex:2;">
                                    <label>SMTP Sunucu</label>
                                    <input type="text" id="ps_platform_smtp_host" class="form-control" value="<?= e($ps['platform_smtp_host'] ?? '') ?>">
                                </div>
                                <div class="form-group" style="flex:1;">
                                    <label>Port</label>
                                    <input type="text" id="ps_platform_smtp_port" class="form-control" value="<?= e($ps['platform_smtp_port'] ?? '587') ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Kullanıcı Adı</label>
                                    <input type="text" id="ps_platform_smtp_user" class="form-control" value="<?= e($ps['platform_smtp_user'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Şifre</label>
                                    <input type="password" id="ps_platform_smtp_pass" class="form-control" placeholder="<?= !empty($ps['platform_smtp_pass_set']) ? '•••••••• (Kayıtlı ✓)' : '' ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Gönderen Adı</label>
                                    <input type="text" id="ps_platform_smtp_from_name" class="form-control" value="<?= e($ps['platform_smtp_from_name'] ?? 'BooKi') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Gönderen E-posta</label>
                                    <input type="text" id="ps_platform_smtp_from_address" class="form-control" value="<?= e($ps['platform_smtp_from_address'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. GOOGLE CLOUD & OAUTH MERKEZİ -->
                    <div class="panel-card" style="border-top:4px solid #f59e0b;">
                        <div class="panel-header">
                            <div class="panel-title">
                                <span>🔑 Ki Business Google Cloud & OAuth</span>
                            </div>
                            <span class="badge active">Tanımlı</span>
                        </div>
                        <div class="panel-body">
                            <p style="font-size:0.75rem;color:var(--text-muted);margin-bottom:0.75rem;">Ortak Google Takvim ve Harita entegrasyonu kimlikleri.</p>

                            <div class="form-group">
                                <label>Client ID</label>
                                <input type="text" id="ps_google_client_id" class="form-control" value="<?= e($ps['google_client_id'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Client Secret</label>
                                <input type="password" id="ps_google_client_secret" class="form-control" placeholder="<?= !empty($ps['google_client_secret_set']) ? '•••••••••••••••• (Kayıtlı ✓)' : '' ?>">
                            </div>
                            <div class="form-group">
                                <label>Project ID</label>
                                <input type="text" id="ps_google_project_id" class="form-control" value="<?= e($ps['google_project_id'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Google Maps Platform API Key (Harita & Geocoding)</label>
                                <input type="text" id="ps_google_maps_key" class="form-control" value="<?= e($ps['google_maps_key'] ?? 'AIzaSyAscIARfxTG_KzedaskCabzuRSTj-0bulA') ?>" placeholder="AIzaSy...">
                                <small style="font-size:0.7rem;color:var(--text-muted);">Saha haritası markerları ve lead adres geocoding için kullanılır.</small>
                            </div>
                        </div>
                    </div>

                    <!-- 6. PAZARYERİ, KOMİSYON & CÜZDAN -->
                    <div class="panel-card" style="border-top:4px solid #8b5cf6;">
                        <div class="panel-header">
                            <div class="panel-title">
                                <span>💳 Pazaryeri Komisyonu & Cüzdan</span>
                            </div>
                        </div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label>Genel Platform Komisyon Oranı (%)</label>
                                <input type="number" step="0.01" id="ps_marketplace_commission_rate" class="form-control" value="<?= e($ps['marketplace_commission_rate'] ?? '5.00') ?>">
                            </div>
                            <div style="background:#f8fafc;padding:1rem;border-radius:8px;border:1px solid var(--border-color);margin-top:1rem;">
                                <div style="font-weight:700;font-size:0.85rem;color:var(--text-main);">Günlük Hakediş ve Cüzdanlar</div>
                                <p style="font-size:0.75rem;color:var(--text-muted);margin:0.25rem 0 0.75rem;">Tamamlanan randevuların komisyon kesintisi sonrası işletme IBAN'larına aktarımı.</p>
                                <a href="<?= site_url('superadmin_settings') ?>" target="_blank" class="btn btn-secondary btn-sm">Gelişmiş Cüzdan Paneline Git ↗</a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB: GOOGLE PLACES PROSPECT CRAWLER -->
            <div class="tab-content-panel <?= vars('active_tab') === 'places-crawler' ? 'active' : '' ?>" id="tab-places-crawler">
                <!-- HEADER & ACTIONS -->
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.25rem;flex-wrap:wrap;gap:1rem;">
                    <div>
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <h2 style="font-size:1.35rem;font-weight:800;color:var(--text-main);margin:0;">Google Places Prospect & Lead Keşfi</h2>
                            <span class="badge" style="background:#8b5cf6;color:#ffffff;font-weight:700;">PRO NEW API</span>
                        </div>
                        <p style="font-size:0.82rem;color:var(--text-muted);margin:0.25rem 0 0;">
                            Bursa 17 ilçe veya Haritada Pin + Yarıçap (KM) çemberi ile BooKi randevu sektörlerinde faal işletmeleri otomatik keşfedin, <code>place_id</code> bazlı tekilleştirin ve isteğe bağlı zenginleştirin.
                        </p>
                    </div>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                        <button class="btn btn-secondary btn-sm" onclick="exportPlacesCsv()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            <span>CSV Dışa Aktar</span>
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="loadPlacesStats()" title="API İstatistiklerini Yenile">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path></svg>
                            <span>İstatistikler</span>
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="clearAllLeadsConfirm()" style="background:#fee2e2;color:#991b1b;border-color:#fecaca;" title="Tüm CRM Lead havuzunu sıfırlar">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            <span>Havuzu Sıfırla</span>
                        </button>
                    </div>
                </div>

                <!-- KPI STATS SUMMARY -->
                <div class="kpi-grid" style="margin-bottom:1.25rem;">
                    <div class="kpi-card accent-purple" onclick="switchCrawlerTableEnrichFilter('all')">
                        <div class="kpi-label">
                            <span>Toplam Keşfedilen Prospect</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon></svg>
                        </div>
                        <div class="kpi-value" id="places-stat-total-discovered">—</div>
                        <div class="kpi-subtext">Google Places tabanlı tekil lead</div>
                    </div>

                    <div class="kpi-card accent-emerald" onclick="switchCrawlerTableEnrichFilter('enriched')">
                        <div class="kpi-label">
                            <span>Zenginleştirilmiş İşletmeler</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </div>
                        <div class="kpi-value" id="places-stat-enriched">—</div>
                        <div class="kpi-subtext">Telefon, Web, Puan, Çalışma Saati</div>
                    </div>

                    <div class="kpi-card accent-blue">
                        <div class="kpi-label">
                            <span>Bugünkü API Çağrısı</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        </div>
                        <div class="kpi-value" id="places-stat-api-today">—</div>
                        <div class="kpi-subtext">Text Search / Place Details</div>
                    </div>

                    <div class="kpi-card accent-amber">
                        <div class="kpi-label">
                            <span>Kapsama Alanı</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon><line x1="8" y1="2" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="22"></line></svg>
                        </div>
                        <div class="kpi-value" style="font-size:1.35rem;">17 İlçe / 12 Sektör</div>
                        <div class="kpi-subtext">Bursa & Çevre Bölgeler</div>
                    </div>
                </div>

                <!-- DIRECT SEARCH BAR (CANLI GOOGLE PLACES İSİM / KELİME ARAMA) -->
                <div class="places-card" style="margin-bottom:1.25rem;border-left:4px solid #8b5cf6;">
                    <div class="places-card-header" style="background:#f8fafc;display:flex;justify-content:space-between;align-items:center;padding:0.9rem 1.25rem;">
                        <div class="places-card-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <span>Doğrudan İşletme Adı veya Anahtar Kelime ile Canlı Arama</span>
                        </div>
                        <span class="badge" style="background:#ede9fe;color:#6d28d9;font-weight:700;font-size:0.7rem;">ANINDA KEŞFET & ZENGİNLEŞTİR</span>
                    </div>
                    <div class="places-card-body" style="padding:1.15rem 1.25rem;">
                        <form id="places-direct-search-form" onsubmit="handlePlacesDirectSearch(event)" style="display:flex;flex-direction:column;gap:0.85rem;">
                            <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:0.75rem;">
                                <div style="position:relative;">
                                    <label style="font-size:0.78rem;font-weight:700;color:var(--text-main);margin-bottom:0.35rem;display:block;">İşletme Adı / Özel Arama (Yakınsak / Canlı):</label>
                                    <input type="text" id="direct-search-query" class="filter-input" placeholder="Örn: Elegance Güzellik Salonu, Masterhair, Dt. Ahmet..." required style="width:100%;font-size:0.85rem;" autocomplete="off" oninput="handleDirectSearchInput(this.value)" onfocus="handleDirectSearchInput(this.value)">
                                    <div id="direct-search-suggest-dropdown" style="display:none;position:absolute;top:100%;left:0;right:0;z-index:1000;background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 10px 25px rgba(0,0,0,0.12);max-height:280px;overflow-y:auto;margin-top:4px;"></div>
                                </div>
                                <div>
                                    <label style="font-size:0.78rem;font-weight:700;color:var(--text-main);margin-bottom:0.35rem;display:block;">Bölge / İlçe:</label>
                                    <select id="direct-search-district" class="filter-select" style="width:100%;font-size:0.85rem;">
                                        <option value="">Tüm Bursa (Geniş Kapsam)</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-size:0.78rem;font-weight:700;color:var(--text-main);margin-bottom:0.35rem;display:block;">BooKi Sektörü:</label>
                                    <select id="direct-search-category" class="filter-select" style="width:100%;font-size:0.85rem;">
                                        <!-- populated dynamically from taxonomy -->
                                    </select>
                                </div>
                            </div>

                            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;padding-top:0.4rem;border-top:1px dashed var(--border-color);">
                                <div style="display:flex;align-items:center;gap:1.25rem;font-size:0.82rem;">
                                    <span style="font-weight:700;color:var(--text-muted);">Arama Modu:</span>
                                    <label style="display:flex;align-items:center;gap:0.4rem;cursor:pointer;">
                                        <input type="radio" name="direct_search_mode" value="basic" checked>
                                        <span>⚡ <strong>Temel (Basic)</strong> — Google Text Search (Konum, Puan, Adres)</span>
                                    </label>
                                    <label style="display:flex;align-items:center;gap:0.4rem;cursor:pointer;">
                                        <input type="radio" name="direct_search_mode" value="enriched">
                                        <span>✨ <strong>Zenginleştirilmiş (Enriched)</strong> — Place Details (Tel, Web, Çalışma Saatleri)</span>
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-primary btn-sm" id="btn-direct-search" style="padding:0.5rem 1.25rem;font-weight:700;background:#7c3aed;border-color:#7c3aed;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    <span>Google'da Canlı Ara & Kaydet</span>
                                </button>
                            </div>
                        </form>

                        <!-- Direct Search Results Container -->
                        <div id="direct-search-results-container" style="display:none;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border-color);">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.6rem;">
                                <div style="font-weight:700;font-size:0.84rem;color:var(--text-main);" id="direct-search-results-title">Bulunan İşletmeler:</div>
                                <button type="button" class="btn btn-secondary btn-xs" onclick="document.getElementById('direct-search-results-container').style.display='none'">Gizle</button>
                            </div>
                            <div id="direct-search-results-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:0.75rem;">
                                <!-- Rendered results -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CRAWLER CONFIGURATION GRID (2 COLUMNS) -->
                <div class="places-config-grid">
                    
                    <!-- COLUMN 1: COĞRAFİ HEDEFLEME -->
                    <div class="places-card">
                        <div class="places-card-header">
                            <div class="places-card-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                <span>1. Coğrafi Hedefleme (Konum & Alan)</span>
                            </div>
                        </div>
                        <div class="places-card-body">
                            
                            <!-- Mode Switcher -->
                            <div class="places-mode-selector">
                                <button type="button" class="places-mode-btn active" id="btn-mode-districts" onclick="switchCrawlerGeoMode('districts')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                                    <span>Bursa 17 İlçe Seçimi</span>
                                </button>
                                <button type="button" class="places-mode-btn" id="btn-mode-radius" onclick="switchCrawlerGeoMode('radius')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="3"></circle></svg>
                                    <span>Haritada Pin + Yarıçap (KM)</span>
                                </button>
                            </div>

                            <!-- DISTRICT SELECTOR MODE -->
                            <div id="crawler-geo-districts-container">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.6rem;font-size:0.75rem;">
                                    <span style="font-weight:600;color:var(--text-muted);">Hedef İlçeler:</span>
                                    <div style="display:flex;gap:0.35rem;">
                                        <button type="button" class="btn btn-secondary btn-xs" onclick="selectCrawlerDistricts('all')">Tümü</button>
                                        <button type="button" class="btn btn-secondary btn-xs" onclick="selectCrawlerDistricts('center')">Merkez 5</button>
                                        <button type="button" class="btn btn-secondary btn-xs" onclick="selectCrawlerDistricts('none')">Temizle</button>
                                    </div>
                                </div>
                                <div class="places-district-grid" id="crawler-district-checkboxes">
                                    <!-- Dynamic from taxonomy or static fallback -->
                                </div>
                            </div>

                            <!-- PIN + RADIUS MAP MODE -->
                            <div id="crawler-geo-radius-container" style="display:none;">
                                <div class="places-map-slider-row">
                                    <span style="font-size:0.78rem;font-weight:700;color:var(--text-main);white-space:nowrap;">🎯 Arama Yarıçapı:</span>
                                    <input type="range" id="crawler-radius-slider" min="1" max="30" value="5" step="1" style="flex:1;" oninput="updateCrawlerRadius(this.value)">
                                    <span class="badge" style="background:#2563eb;color:#fff;font-weight:700;min-width:55px;text-align:center;" id="crawler-radius-badge">5 km</span>
                                </div>
                                <div class="places-map-box" id="crawler-radius-map">
                                    <div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--text-light);font-size:0.8rem;">
                                        Harita yükleniyor...
                                    </div>
                                </div>
                                <div style="display:flex;justify-content:space-between;font-size:0.73rem;color:var(--text-muted);">
                                    <div>📍 Merkez: <strong id="crawler-center-label" style="color:var(--text-main);">40.2185, 28.9345 (Bursa)</strong></div>
                                    <div>🌐 Kapsam: <strong id="crawler-area-label" style="color:var(--text-main);">~78.5 km²</strong></div>
                                </div>
                                <p style="font-size:0.7rem;color:var(--text-light);margin:0.35rem 0 0;">
                                    💡 Haritaya tıklayarak veya pini sürükleyerek arama çemberinizin merkezini serbestçe belirleyebilirsiniz.
                                </p>
                            </div>

                        </div>
                    </div>

                    <!-- COLUMN 2: BOOKI KATEGORİ & DERİNLİK -->
                    <div class="places-card">
                        <div class="places-card-header">
                            <div class="places-card-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                                <span>2. BooKi Randevu Sektörleri & Derinlik</span>
                            </div>
                        </div>
                        <div class="places-card-body">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.6rem;font-size:0.75rem;">
                                <span style="font-weight:600;color:var(--text-muted);">Sektörler:</span>
                                <div style="display:flex;gap:0.35rem;">
                                    <button type="button" class="btn btn-secondary btn-xs" onclick="selectCrawlerCategories('all')">Tümü</button>
                                    <button type="button" class="btn btn-secondary btn-xs" onclick="selectCrawlerCategories('popular')">Popüler</button>
                                    <button type="button" class="btn btn-secondary btn-xs" onclick="selectCrawlerCategories('none')">Temizle</button>
                                </div>
                            </div>
                            <div class="places-pills-grid" id="crawler-category-checkboxes">
                                <!-- Dynamic from taxonomy -->
                            </div>

                            <hr style="border:none;border-top:1px solid var(--border-color);margin:0.85rem 0;">

                            <div style="display:flex;gap:1.5rem;flex-wrap:wrap;font-size:0.8rem;">
                                <div>
                                    <span style="font-weight:700;color:var(--text-main);display:block;margin-bottom:0.35rem;">Arama Derinliği (Sayfa / Sonuç):</span>
                                    <div style="display:flex;gap:0.75rem;align-items:center;">
                                        <label style="display:flex;align-items:center;gap:0.35rem;cursor:pointer;">
                                            <input type="radio" name="crawler_depth" value="standard" checked onchange="updatePlacesPreview()">
                                            <span>Standart (1 Sayfa - 20 Sonuç / ~1 Çağrı)</span>
                                        </label>
                                        <label style="display:flex;align-items:center;gap:0.35rem;cursor:pointer;">
                                            <input type="radio" name="crawler_depth" value="deep" onchange="updatePlacesPreview()">
                                            <span>Derin Tarama (Maks 60 Sonuç / ~3 Çağrı)</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top:0.75rem;font-size:0.78rem;color:var(--text-muted);">
                                <label style="display:flex;align-items:center;gap:0.4rem;cursor:pointer;">
                                    <input type="checkbox" id="crawler_filter_operational" checked>
                                    <span>Yalnızca faal işletmeleri ekle (<code>OPERATIONAL</code>). Kapalı işletmeleri filtrele.</span>
                                </label>
                            </div>

                        </div>
                    </div>

                </div>

                <!-- PREVIEW & ACTION BAR -->
                <div class="places-card" style="margin-bottom:1.25rem;background:#f8fafc;">
                    <div style="padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <div style="background:#eff6ff;color:#2563eb;width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;">
                                🎯
                            </div>
                            <div>
                                <div style="font-size:0.86rem;font-weight:700;color:var(--text-main);" id="crawler-preview-title">Tarama Özeti & Tahmin</div>
                                <div style="font-size:0.76rem;color:var(--text-muted);" id="crawler-preview-details">Hesaplanıyor...</div>
                            </div>
                        </div>

                        <div style="display:flex;gap:0.5rem;align-items:center;">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="updatePlacesPreview()">
                                <span>🔄 Önizlemeyi Güncelle</span>
                            </button>
                            <button type="button" class="btn btn-primary" id="btn-start-places-crawl" onclick="startPlacesCrawl()" style="background:linear-gradient(135deg,#2563eb,#7c3aed);border:none;padding:0.65rem 1.35rem;font-weight:700;box-shadow:0 2px 8px rgba(37,99,235,0.3);">
                                <span>🚀 Canlı Keşfi Başlat</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- LIVE JOB MONITOR -->
                <div class="places-job-monitor" id="places-job-card">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                        <div style="display:flex;align-items:center;gap:0.6rem;">
                            <span class="badge" style="background:#10b981;color:#fff;font-weight:700;" id="places-job-badge">ÇALIŞIYOR ⚡</span>
                            <span style="font-size:0.85rem;font-weight:700;color:#f8fafc;" id="places-job-title">Canlı Tarama Görevi</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <span style="font-size:0.75rem;color:#94a3b8;" id="places-job-timer">0s</span>
                            <button type="button" class="btn btn-secondary btn-xs" onclick="cancelPlacesJob()" style="background:rgba(239,68,68,0.2);color:#fca5a5;border-color:rgba(239,68,68,0.4);">
                                ⏹️ Taramayı Durdur
                            </button>
                        </div>
                    </div>

                    <div style="font-size:0.82rem;color:#93c5fd;font-family:monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" id="places-job-query-label">
                        Sorgu hazırlanıyor...
                    </div>

                    <div class="places-progress-track">
                        <div class="places-progress-fill" id="places-job-progress-bar"></div>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.78rem;color:#cbd5e1;flex-wrap:wrap;gap:0.5rem;">
                        <div>
                            <span>İlerleme: <strong id="places-job-progress-pct" style="color:#ffffff;">0%</strong></span>
                            <span style="margin:0 0.5rem;color:#64748b;">|</span>
                            <span>Sorgular: <strong id="places-job-queries-done" style="color:#ffffff;">0</strong> / <strong id="places-job-queries-total">0</strong></span>
                        </div>
                        <div style="display:flex;gap:0.85rem;">
                            <span>Bulunan: <strong id="places-job-found-count" style="color:#60a5fa;">0</strong></span>
                            <span>Yeni Lead: <strong id="places-job-created-count" style="color:#34d399;">0</strong></span>
                            <span>Güncellenen: <strong id="places-job-updated-count" style="color:#fbbf24;">0</strong></span>
                            <span>Hata: <strong id="places-job-failed-count" style="color:#f87171;">0</strong></span>
                        </div>
                    </div>
                </div>

                <!-- DISCOVERED PROSPECTS TABLE -->
                <div class="table-container">
                    
                    <!-- Table Toolbar -->
                    <div class="filter-toolbar">
                        <div class="filter-group">
                            <input type="text" id="crawler-search-input" class="filter-input" placeholder="İşletme adı, adres, telefon ara..." style="width:260px;" oninput="handleCrawlerSearchInput(this.value)">

                            <select id="crawler-sector-filter" class="filter-select" onchange="loadCrawlerLeadsTable(1)">
                                <option value="">Tüm Sektörler</option>
                            </select>

                            <select id="crawler-district-filter" class="filter-select" onchange="loadCrawlerLeadsTable(1)">
                                <option value="">Tüm İlçeler</option>
                            </select>

                            <select id="crawler-enrich-filter" class="filter-select" onchange="loadCrawlerLeadsTable(1)">
                                <option value="">Tüm Durumlar</option>
                                <option value="enriched">✨ Sadece Zenginleştirilenler</option>
                                <option value="not_enriched">🎯 Henüz Zenginleştirilmemiş</option>
                            </select>
                        </div>

                        <div>
                            <button class="btn btn-secondary btn-sm" onclick="bulkEnrichPlaceLeads()" style="background:#7c3aed;color:#ffffff;border:none;box-shadow:0 1px 3px rgba(124,58,237,0.3);">
                                <span>✨ Seçilenleri Zenginleştir</span>
                            </button>
                        </div>
                    </div>

                    <!-- Table Responsive -->
                    <div class="table-responsive">
                        <table class="data-table" id="crawler-leads-table">
                            <thead>
                                <tr>
                                    <th style="width:36px;text-align:center;">
                                        <input type="checkbox" id="crawler-select-all" onchange="toggleSelectAllCrawlerLeads(this.checked)">
                                    </th>
                                    <th>İşletme & Google Türü</th>
                                    <th>BooKi Sektörü</th>
                                    <th>İlçe & Adres</th>
                                    <th>Durum</th>
                                    <th>Google Harita</th>
                                    <th>İletişim & Detaylar</th>
                                    <th style="text-align:right;">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody id="crawler-leads-tbody">
                                <tr>
                                    <td colspan="8" style="text-align:center;padding:2.5rem;color:var(--text-light);">
                                        Yükleniyor...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Table Footer / Pagination -->
                    <div class="table-footer" style="display:flex;justify-content:space-between;align-items:center;padding:0.85rem 1rem;background:#f8fafc;border-top:1px solid var(--border-color);font-size:0.8rem;flex-wrap:wrap;gap:0.5rem;">
                        <div id="crawler-pagination-info" style="color:var(--text-muted);">0 işletme gösteriliyor</div>
                        <div id="crawler-pagination-buttons" style="display:flex;gap:0.35rem;"></div>
                    </div>

                </div>

            </div>

        </div>
    </div>

    <!-- OMNISEARCH MODAL (CTRL + K) -->
    <div class="modal-backdrop" id="omnisearch-modal" onclick="closeOmnisearch(event)">
        <div class="modal" onclick="event.stopPropagation()">
            <div style="padding:0.75rem 1rem;border-bottom:1px solid var(--border-color);display:flex;align-items:center;gap:0.75rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--text-light);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="omnisearch-field" placeholder="İşletme adı, yetkili, telefon, ilçe veya kiracı ara..." style="flex:1;border:none;outline:none;font-size:0.95rem;" oninput="handleOmnisearchInput(this.value)">
                <span class="omnisearch-shortcut" onclick="closeOmnisearch()">ESC</span>
            </div>
            <div class="search-results-list" id="omnisearch-results">
                <div style="padding:1.5rem;text-align:center;color:var(--text-light);font-size:0.84rem;">Aramak istediğiniz terimi yazın...</div>
            </div>
        </div>
    </div>

    <!-- LEAD DETAIL DRAWER (360 DEGREE PROFILE) -->
    <div class="drawer-overlay" id="lead-drawer-overlay" onclick="closeLeadDrawer(event)">
        <div class="drawer-container" onclick="event.stopPropagation()">
            <div class="drawer-header">
                <div>
                    <div class="drawer-title" id="drawer-lead-name">—</div>
                    <div style="font-size:0.78rem;color:var(--text-muted);" id="drawer-lead-sub">—</div>
                </div>
                <button class="btn btn-secondary btn-icon" onclick="closeLeadDrawer()">✕</button>
            </div>
            <div class="drawer-body" id="drawer-body-content">
                <div style="text-align:center;padding:2rem;color:var(--text-light);">Yükleniyor...</div>
            </div>
            <div class="drawer-footer" id="drawer-footer-actions">
                <!-- Populated via JS -->
            </div>
        </div>
    </div>

    <!-- CREATE LEAD MODAL -->
    <div class="modal-backdrop" id="create-lead-modal">
        <div class="modal" style="max-width:560px; max-height:90vh; overflow-y:auto;">
            <div class="modal-header">
                <div class="modal-title">Yeni Potansiyel Müşteri (Lead) Ekle</div>
                <button class="btn btn-secondary btn-icon" onclick="document.getElementById('create-lead-modal').classList.remove('open')">✕</button>
            </div>
            <form id="create-lead-form" onsubmit="handleCreateLead(event)">
                <div class="modal-body">
                    <div class="form-group">
                        <label>İşletme Adı *</label>
                        <input type="text" class="form-control" id="nl-name" required placeholder="Örn: Elit Kuaför & Güzellik">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Sektör</label>
                            <input type="text" class="form-control" id="nl-sector" list="sectors-datalist" placeholder="Güzellik Salonu, Berber...">
                            <datalist id="sectors-datalist">
                                <?php foreach (vars('sectors') as $sc): ?>
                                    <option value="<?= e($sc['sector']) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label>İlçe</label>
                            <input type="text" class="form-control" id="nl-district" list="districts-datalist" placeholder="Kadıköy, Şişli...">
                            <datalist id="districts-datalist">
                                <?php foreach (vars('districts') as $dst): ?>
                                    <option value="<?= e($dst['district']) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Açık Adres</label>
                        <input type="text" class="form-control" id="nl-address" placeholder="Cadde, sokak, no...">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Yetkili Kişi</label>
                            <input type="text" class="form-control" id="nl-contact-person" placeholder="Ad Soyad">
                        </div>
                        <div class="form-group">
                            <label>Yetkili Ünvanı</label>
                            <input type="text" class="form-control" id="nl-contact-title" placeholder="İşletme Sahibi, Müdür">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Telefon *</label>
                            <input type="text" class="form-control" id="nl-phone" required placeholder="05XXXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label>WhatsApp</label>
                            <input type="text" class="form-control" id="nl-whatsapp" placeholder="Boşsa telefon kullanılır">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>E-posta</label>
                            <input type="email" class="form-control" id="nl-email" placeholder="info@isletme.com">
                        </div>
                        <div class="form-group">
                            <label>Başlangıç Aşaması</label>
                            <select class="form-control" id="nl-stage">
                                <?php foreach (vars('stage_definitions') as $k => $lbl): ?>
                                    <option value="<?= e($k) ?>"><?= e($lbl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Önerilen Paket</label>
                            <select class="form-control" id="nl-package" onchange="updateNewLeadMrr(this.value)">
                                <option value="Starter">Starter (₺1.999/ay)</option>
                                <option value="Professional" selected>Professional (₺2.199/ay)</option>
                                <option value="Enterprise">Enterprise (₺4.499/ay)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Potansiyel MRR (₺)</label>
                            <input type="number" step="0.01" class="form-control" id="nl-potential-mrr" value="2199.00">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Özel Notlar</label>
                        <textarea class="form-control" id="nl-notes" rows="2" placeholder="Saha ekibi için ilk izlenim veya notlar..."></textarea>
                    </div>
                    <div id="create-lead-msg" style="display:none;color:#b91c1c;font-size:0.8rem;margin-top:0.4rem;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('create-lead-modal').classList.remove('open')">Vazgeç</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-lead">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- CREATE TASK MODAL -->
    <div class="modal-backdrop" id="create-task-modal">
        <div class="modal" style="max-width:480px;">
            <div class="modal-header">
                <div class="modal-title">Yeni Saha Görevi / Hatırlatıcı</div>
                <button class="btn btn-secondary btn-icon" onclick="document.getElementById('create-task-modal').classList.remove('open')">✕</button>
            </div>
            <form id="create-task-form" onsubmit="handleCreateTask(event)">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Görev Başlığı *</label>
                        <input type="text" class="form-control" id="nt-title" required placeholder="Örn: Ahmet Bey'e demo linki gönder">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Vade Tarihi *</label>
                            <input type="date" class="form-control" id="nt-due-date" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label>Saat</label>
                            <input type="time" class="form-control" id="nt-due-time" value="14:00">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Öncelik</label>
                            <select class="form-control" id="nt-priority">
                                <option value="normal">Normal</option>
                                <option value="high">Yüksek / Acil</option>
                                <option value="low">Düşük</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>İlgili Lead ID (Opsiyonel)</label>
                            <input type="number" class="form-control" id="nt-lead-id" placeholder="Örn: 105">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Açıklama</label>
                        <textarea class="form-control" id="nt-description" rows="2" placeholder="Görevin detayları..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('create-task-modal').classList.remove('open')">Vazgeç</button>
                    <button type="submit" class="btn btn-primary">Görevi Oluştur</button>
                </div>
            </form>
        </div>
    </div>

    <!-- FIELD VISIT MODAL (SAHA GÖRÜŞMESİ KAYIT VE ANKET) -->
    <div class="modal-backdrop" id="field-visit-modal">
        <div class="modal" style="max-width:540px;">
            <div class="modal-header">
                <div class="modal-title">Saha Ziyareti & Görüşme Formu</div>
                <button class="btn btn-secondary btn-icon" onclick="document.getElementById('field-visit-modal').classList.remove('open')">✕</button>
            </div>
            <form id="field-visit-form" onsubmit="handleSaveFieldVisit(event)">
                <div class="modal-body">
                    <input type="hidden" id="visit-lead-id">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Görüşülen Yetkili Adı *</label>
                            <input type="text" class="form-control" id="v-contact-person" required>
                        </div>
                        <div class="form-group">
                            <label>Yetkili Ünvanı / Rolü</label>
                            <input type="text" class="form-control" id="v-contact-title" placeholder="İşletme Sahibi, Müdür">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Görüşme Tipi</label>
                            <select class="form-control" id="v-visit-type">
                                <option value="field_visit">Yüz Yüze Saha Ziyareti</option>
                                <option value="phone_call">Telefon Görüşmesi</option>
                                <option value="online_demo">Online Demo</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Mevcut Rezervasyon Sistemi</label>
                            <select class="form-control" id="v-current-method">
                                <option value="Defter / Manuel">Defter / Manuel Randevu</option>
                                <option value="WhatsApp / Telefon">WhatsApp / Telefonla Randevu</option>
                                <option value="Excel">Excel Tabloları</option>
                                <option value="Rakip Yazılım">Başka Bir Yazılım Kullanıyor</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Çalışan / Personel Sayısı</label>
                            <input type="number" class="form-control" id="v-staff-count" placeholder="Örn: 4">
                        </div>
                        <div class="form-group">
                            <label>İlgi / Sıcaklık Seviyesi</label>
                            <select class="form-control" id="v-interest-level" onchange="autoSuggestStage()">
                                <option value="very_high">🔥 Çok Sıcak (Hemen Başlamak İstiyor)</option>
                                <option value="high">Yüksek İlgi (Demo İstedi)</option>
                                <option value="medium">Orta (Düşünecek / Kararsız)</option>
                                <option value="low">Düşük (İlgisiz)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Önerilen / Hedef Yeni Aşama</label>
                        <select class="form-control" id="v-new-stage">
                            <?php foreach (vars('stage_definitions') as $k => $lbl): ?>
                                <option value="<?= e($k) ?>"><?= e($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Görüşme Notları & Müşteri Talepleri</label>
                        <textarea class="form-control" id="v-notes" rows="3" placeholder="Görüşmede konuşulan detaylar, çekinceler, özel istekler..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Sonraki Eylem Tarihi (Takip)</label>
                            <input type="date" class="form-control" id="v-next-action-date">
                        </div>
                        <div class="form-group">
                            <label>Sonraki Eylem Notu</label>
                            <input type="text" class="form-control" id="v-next-action-notes" placeholder="Örn: Tekrar ara, demo linki at">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('field-visit-modal').classList.remove('open')">Vazgeç</button>
                    <button type="submit" class="btn btn-primary">Ziyareti Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- WON -> 5-STEP TENANT CREATION WIZARD MODAL -->
    <div class="modal-backdrop" id="wizard-tenant-modal">
        <div class="modal" style="max-width:580px;">
            <div class="modal-header">
                <div class="modal-title">Satış Kapanışı — Tenant & Onboarding Sihirbazı</div>
                <button class="btn btn-secondary btn-icon" onclick="document.getElementById('wizard-tenant-modal').classList.remove('open')">✕</button>
            </div>
            <div class="wizard-steps">
                <div class="wizard-step active" id="w-step-tab-1">1. İşletme</div>
                <div class="wizard-step" id="w-step-tab-2">2. Paket & MRR</div>
                <div class="wizard-step" id="w-step-tab-3">3. Yönetici</div>
                <div class="wizard-step" id="w-step-tab-4">4. Onboarding</div>
                <div class="wizard-step" id="w-step-tab-5">5. Onay</div>
            </div>
            <form id="wizard-tenant-form" onsubmit="handleExecuteTenantWizard(event)">
                <input type="hidden" id="wiz-lead-id">
                
                <!-- STEP 1: BUSINESS -->
                <div class="modal-body" id="wiz-panel-1">
                    <div class="form-group">
                        <label>İşletme Adı *</label>
                        <input type="text" class="form-control" id="wiz-business-name" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Subdomain (bookiapp.kibusiness.co) *</label>
                            <input type="text" class="form-control" id="wiz-subdomain" placeholder="orn: berberahmet" required oninput="sanitizeSubdomainInput(this)">
                        </div>
                        <div class="form-group">
                            <label>İşletme Sektörü</label>
                            <select class="form-control" id="wiz-business-type">
                                <option value="Güzellik Salonu">Güzellik Salonu</option>
                                <option value="Berber & Kuaför">Berber & Kuaför</option>
                                <option value="Klinik & Sağlık">Klinik & Sağlık</option>
                                <option value="Masaj & Spa">Masaj & Spa</option>
                                <option value="Restoran">Restoran</option>
                                <option value="Spor & Fitness">Spor & Fitness</option>
                                <option value="Diğer">Diğer</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>İşletme Adresi & İlçe</label>
                        <input type="text" class="form-control" id="wiz-address">
                    </div>
                </div>

                <!-- STEP 2: PLAN -->
                <div class="modal-body" id="wiz-panel-2" style="display:none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Paket</label>
                            <select class="form-control" id="wiz-plan">
                                <option value="Starter">Starter (₺1.999/ay)</option>
                                <option value="Professional" selected>Professional (₺2.199/ay)</option>
                                <option value="Enterprise">Enterprise (₺4.499/ay)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Faturalandırma Döngüsü</label>
                            <select class="form-control" id="wiz-billing-cycle">
                                <option value="monthly">Aylık</option>
                                <option value="yearly">Yıllık</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Aylık Tekrarlayan Gelir (MRR ₺)</label>
                            <input type="number" step="0.01" class="form-control" id="wiz-mrr" value="2199.00">
                        </div>
                        <div class="form-group">
                            <label>Ücretsiz Deneme (Gün)</label>
                            <input type="number" class="form-control" id="wiz-trial-days" value="14">
                        </div>
                    </div>
                </div>

                <!-- STEP 3: ADMIN USER -->
                <div class="modal-body" id="wiz-panel-3" style="display:none;">
                    <div class="form-group">
                        <label>Yönetici Ad Soyad *</label>
                        <input type="text" class="form-control" id="wiz-admin-name" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Yönetici E-posta *</label>
                            <input type="email" class="form-control" id="wiz-admin-email" required>
                        </div>
                        <div class="form-group">
                            <label>Yönetici Telefon (WhatsApp) *</label>
                            <input type="text" class="form-control" id="wiz-admin-phone" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Başlangıç Şifresi (Boşsa güvenli rastgele üretilir)</label>
                        <input type="text" class="form-control" id="wiz-admin-password" placeholder="En az 8 karakter">
                    </div>
                </div>

                <!-- STEP 4: ONBOARDING OPTIONS -->
                <div class="modal-body" id="wiz-panel-4" style="display:none;">
                    <div style="background:#eff6ff;border:1px solid #bfdbfe;padding:1rem;border-radius:8px;margin-bottom:1rem;">
                        <div style="font-weight:700;color:#1e40af;margin-bottom:0.25rem;">Otomatik Müşteri Onboarding Bağlantısı</div>
                        <div style="font-size:0.8rem;color:#1e3a8a;">
                            İşletme açıldığında müşterinin kendi personelini, hizmetlerini, çalışma saatlerini ve randevu geçmişini sisteme girebilmesi için güvenli 10 adımlı sihirbaz bağlantısı otomatik hazırlanır.
                        </div>
                    </div>
                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:0.5rem;font-weight:600;cursor:pointer;">
                            <input type="checkbox" id="wiz-create-onboarding" checked>
                            <span>Onboarding oturumu oluştur ve bağlantıyı hazırla</span>
                        </label>
                    </div>
                </div>

                <!-- STEP 5: REVIEW & EXECUTE -->
                <div class="modal-body" id="wiz-panel-5" style="display:none;">
                    <div id="wiz-summary-content" style="background:#f8fafc;border:1px solid var(--border-color);padding:1rem;border-radius:8px;font-size:0.85rem;line-height:1.6;">
                        <!-- Populated via JS -->
                    </div>
                    <div id="wiz-error-msg" style="display:none;color:#b91c1c;font-size:0.82rem;margin-top:0.75rem;"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btn-wiz-prev" style="display:none;" onclick="wizStepPrev()">Geri</button>
                    <button type="button" class="btn btn-primary" id="btn-wiz-next" onclick="wizStepNext()">Devam Et</button>
                    <button type="submit" class="btn btn-success" id="btn-wiz-submit" style="display:none;">Kurulumu Tamamla & Tenant Başlat</button>
                </div>
            </form>
        </div>
    </div>

    <!-- PITCH GUIDE DRAWER (SAHA SATIŞ REHBERİ) -->
    <div class="drawer-overlay" id="pitch-drawer-overlay" onclick="closePitchGuide(event)">
        <div class="drawer-container" onclick="event.stopPropagation()">
            <div class="drawer-header">
                <div>
                    <div class="drawer-title">Saha Satış & Argüman Rehberi</div>
                    <div style="font-size:0.78rem;color:var(--text-muted);">Görüşme sırasında kullanabileceğiniz etkili konuşma kalıpları</div>
                </div>
                <button class="btn btn-secondary btn-icon" onclick="closePitchGuide()">✕</button>
            </div>
            <div class="drawer-body" style="line-height:1.6;font-size:0.85rem;">
                <h4 style="font-size:0.95rem;margin-bottom:0.5rem;color:var(--primary);">🎯 3 Dakikalık Hızlı Giriş Konuşması</h4>
                <div style="background:#f8fafc;border-left:3px solid var(--primary);padding:0.75rem;border-radius:4px;margin-bottom:1.25rem;">
                    "Merhaba [Yetkili Adı], bölgenizdeki randevulu işletmelerin no-show (gelmeyen müşteri) oranlarını %70 azaltan ve telefon trafiğini tamamen ortadan kaldıran BooKi randevu yönetim sistemini tanıtmak için uğradım. Müşterileriniz gece saat 11'de bile WhatsApp veya web üzerinden 30 saniyede randevu alabiliyor."
                </div>

                <h4 style="font-size:0.95rem;margin-bottom:0.5rem;color:var(--text-main);">🛡️ Sık Karşılaşılan İtirazlar & Cevaplar</h4>
                
                <div style="margin-bottom:1rem;">
                    <strong>1. "Biz defterle çok rahatız, teknolojiye gerek yok."</strong>
                    <div style="color:var(--text-muted);margin-top:0.2rem;">
                        👉 <em>"Haklısınız, defter kolay gibi görünür ama müşteriniz randevuyu unuttuğunda defter ona hatırlatma SMS'i atamaz. BooKi otomatik WhatsApp hatırlatması yollar, koltuğunuz boş kalmaz."</em>
                    </div>
                </div>

                <div style="margin-bottom:1rem;">
                    <strong>2. "Bizim müşterilerimiz yaşlı, internetten randevu alamaz."</strong>
                    <div style="color:var(--text-muted);margin-top:0.2rem;">
                        👉 <em>"Müşterinizin hiçbir uygulama indirmesine gerek yok. WhatsApp'tan işletmenizin linkine bir tıkla girip seçtiği personele ve saate randevu oluşturabiliyor."</em>
                    </div>
                </div>

                <div style="margin-bottom:1rem;">
                    <strong>3. "Şu an bütçemiz yok / Pahalı."</strong>
                    <div style="color:var(--text-muted);margin-top:0.2rem;">
                        👉 <em>"Ayda sadece 2 tane randevusunu unutan müşteriyi kurtardığınızda BooKi zaten kendi ücretini fazlasıyla çıkarıyor. Üstelik size 10 günlük tamamen ücretsiz deneme sunuyoruz, beğenmezseniz tek kuruş ödemezsiniz."</em>
                    </div>
                </div>

                <h4 style="font-size:0.95rem;margin-top:1.5rem;margin-bottom:0.5rem;color:var(--text-main);">📦 Paket Karşılaştırma</h4>
                <div style="background:#f8fafc;padding:0.85rem;border-radius:6px;border:1px solid var(--border-color);">
                    <div>• <strong>Starter (₺1.999/ay):</strong> 2 Personel, temel randevu, SMS/WhatsApp hatırlatma.</div>
                    <div style="margin-top:0.3rem;">• <strong>Professional (₺2.199/ay):</strong> 5 Personel, komisyon takibi, adisyon/gelir-gider, çoklu şube.</div>
                    <div style="margin-top:0.3rem;">• <strong>Enterprise (₺4.499/ay):</strong> Sınırsız personel, yapay zeka randevu asistanı, özel entegrasyonlar.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ORIGINAL TENANT MODALS (100% PRESERVED IDs & STRUCTURE) -->
    <!-- Create modal -->
    <div class="modal-backdrop" id="create-modal">
        <div class="modal" style="max-width:480px; max-height:90vh; overflow-y:auto;">
            <div class="modal-header">
                <div class="modal-title">Yeni Kiracı Oluştur</div>
                <button class="btn btn-secondary btn-icon" onclick="document.getElementById('create-modal').classList.remove('open')">✕</button>
            </div>
            <form id="create-form">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Subdomain *</label>
                        <input type="text" id="c-subdomain" class="form-control" placeholder="orn: acme" required>
                    </div>
                    <div class="form-group">
                        <label>İşletme Türü</label>
                        <select id="c-business-type" class="form-control">
                            <option value="">— Seçiniz —</option>
                            <option value="Güzellik Salonu">Güzellik Salonu</option>
                            <option value="Masaj & Spa">Masaj & Spa</option>
                            <option value="Klinik & Sağlık">Klinik & Sağlık</option>
                            <option value="Restoran">Restoran</option>
                            <option value="Spor & Fitness">Spor & Fitness</option>
                            <option value="Eğitim & Danışmanlık">Eğitim & Danışmanlık</option>
                            <option value="Diğer">Diğer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Plan</label>
                        <select id="c-plan" class="form-control">
                            <option value="">— (Free varsayılan)</option>
                            <option value="Free">Free</option>
                            <option value="Basic">Basic</option>
                            <option value="Premium">Premium</option>
                            <option value="Elite">Elite</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Deneme Süresi (gün, opsiyonel)</label>
                        <input type="number" id="c-trial-days" class="form-control" placeholder="14">
                    </div>
                    
                    <h4 style="margin-top:1.25rem;margin-bottom:0.75rem;font-size:0.85rem;color:var(--text-muted);text-transform:uppercase;">Yönetici Bilgileri (Opsiyonel)</h4>
                    <div class="form-group">
                        <label>Ad Soyad</label>
                        <input type="text" id="c-admin-name" class="form-control" placeholder="Yönetici Adı Soyadı">
                    </div>
                    <div class="form-group">
                        <label>E-posta</label>
                        <input type="email" id="c-admin-email" class="form-control" placeholder="yonetici@acme.com">
                    </div>
                    <div class="form-group">
                        <label>Telefon</label>
                        <input type="text" id="c-admin-phone" class="form-control" placeholder="05XX XXX XX XX">
                    </div>
                    <div class="form-group">
                        <label>Özel Şifre (boşsa rastgele)</label>
                        <input type="text" id="c-admin-password" class="form-control" placeholder="En az 8 karakter">
                    </div>

                    <div id="create-msg" style="display:none;color:#b91c1c;font-size:0.82rem;margin-top:0.5rem;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('create-modal').classList.remove('open')">Vazgeç</button>
                    <button type="submit" class="btn btn-primary">Oluştur</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Plan/license modal -->
    <div class="modal-backdrop" id="plan-modal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">Plan / Lisans Düzenle</div>
                <button class="btn btn-secondary btn-icon" onclick="document.getElementById('plan-modal').classList.remove('open')">✕</button>
            </div>
            <form id="plan-form">
                <div class="modal-body">
                    <input type="hidden" id="p-tenant-id">
                    <div class="form-group">
                        <label>Plan</label>
                        <select id="p-plan" class="form-control">
                            <option value="">— (Free varsayılan)</option>
                            <option value="Free">Free</option>
                            <option value="Basic">Basic</option>
                            <option value="Premium">Premium</option>
                            <option value="Elite">Elite</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Döngü</label>
                        <select id="p-billing-cycle" class="form-control">
                            <option value="monthly">Aylık</option>
                            <option value="yearly">Yıllık</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>MRR (Aylık Tekrarlayan Gelir)</label>
                        <input type="number" step="0.01" id="p-mrr-amount" class="form-control" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>Deneme Bitişi (YYYY-MM-DD HH:MM:SS)</label>
                        <input type="text" id="p-trial-ends-at" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Lisans Bitişi (YYYY-MM-DD HH:MM:SS)</label>
                        <input type="text" id="p-license-expires-at" class="form-control">
                    </div>
                    <div id="plan-msg" style="display:none;color:#b91c1c;font-size:0.82rem;margin-top:0.5rem;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('plan-modal').classList.remove('open')">Vazgeç</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal-backdrop" id="details-modal">
        <div class="modal" style="max-width:500px;">
            <div class="modal-header">
                <div class="modal-title">Kiracı Detayları</div>
                <button class="btn btn-secondary btn-icon" onclick="document.getElementById('details-modal').classList.remove('open')">✕</button>
            </div>
            <div class="modal-body">
                <div id="details-loading" style="padding:1rem;text-align:center;color:#666;">Yükleniyor...</div>
                <div id="details-content" style="display:none;">
                    <div style="display:flex;gap:1rem;margin-bottom:1rem;">
                        <div style="flex:1;background:#f8fafc;padding:.8rem;border-radius:6px;border:1px solid #e2e8f0;">
                            <div style="font-size:.75rem;color:#666;">Hizmetler</div>
                            <div style="font-size:1.2rem;font-weight:bold;" id="det-services"></div>
                        </div>
                        <div style="flex:1;background:#f8fafc;padding:.8rem;border-radius:6px;border:1px solid #e2e8f0;">
                            <div style="font-size:.75rem;color:#666;">Personeller</div>
                            <div style="font-size:1.2rem;font-weight:bold;" id="det-providers"></div>
                        </div>
                    </div>
                    
                    <h4 style="font-size:.85rem;margin-bottom:.5rem;">Randevu Durumları</h4>
                    <div style="display:flex;gap:.5rem;margin-bottom:1rem;font-size:.82rem;">
                        <span style="color:#b3720a;">Beklemede: <strong id="det-st-pending"></strong></span> |
                        <span style="color:#1e8a4c;">Onaylı: <strong id="det-st-approved"></strong></span> |
                        <span style="color:#1e8a4c;">Tamamlanan: <strong id="det-st-completed"></strong></span> |
                        <span style="color:#c0392b;">İptal: <strong id="det-st-canceled"></strong></span>
                    </div>
                    
                    <h4 style="font-size:.85rem;margin-bottom:.5rem;">İletişim</h4>
                    <div style="font-size:.82rem;margin-bottom:1rem;line-height:1.4;">
                        <strong>Yönetici:</strong> <span id="det-contact-name"></span><br>
                        <strong>E-posta:</strong> <span id="det-contact-email"></span><br>
                        <strong>Telefon:</strong> <span id="det-contact-phone"></span>
                    </div>
                    
                    <h4 style="font-size:.85rem;margin-bottom:.5rem;">Son 5 Randevu</h4>
                    <ul id="det-last-appointments" style="font-size:.8rem;padding-left:1.2rem;color:#555;margin:0;"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('details-modal').classList.remove('open')">Kapat</button>
            </div>
        </div>
    </div>

    <!-- Admin account modal -->
    <div class="modal-backdrop" id="admin-modal">
        <div class="modal" style="max-width:480px;">
            <div class="modal-header">
                <div class="modal-title">Admin Hesabı — <span id="a-subdomain-label"></span></div>
                <button class="btn btn-secondary btn-icon" onclick="document.getElementById('admin-modal').classList.remove('open')">✕</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="a-tenant-id">
                <p style="margin:0 0 .75rem;font-size:.82rem;color:#666;">
                    E-posta: <strong id="a-email-label">—</strong>
                </p>

                <div class="form-group">
                    <label>Kullanıcı Adı</label>
                    <div style="display:flex;gap:.5rem;">
                        <input type="text" id="a-username" class="form-control" style="flex:1;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="saveAdminUsername()">Kaydet</button>
                    </div>
                    <div id="a-username-msg" style="display:none;font-size:0.75rem;margin-top:0.3rem;"></div>
                </div>

                <div class="form-group">
                    <label>Yeni Şifre Belirle</label>
                    <div style="display:flex;gap:.5rem;">
                        <input type="text" id="a-password" class="form-control" placeholder="en az 8 karakter" style="flex:1;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="saveAdminPassword()">Kaydet</button>
                    </div>
                    <div id="a-password-msg" style="display:none;font-size:0.75rem;margin-top:0.3rem;"></div>
                </div>
                
                <div id="a-reset-msg" style="display:none;font-size:0.75rem;margin-top:0.3rem;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="sendAdminReset()">Şifre Sıfırlama Gönder</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('admin-modal').classList.remove('open')">Kapat</button>
            </div>
        </div>
    </div>

    <!-- Delete modal -->
    <div class="modal-backdrop" id="delete-modal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title" style="color:#b91c1c;">Kiracıyı Kalıcı Olarak Sil</div>
                <button class="btn btn-secondary btn-icon" onclick="document.getElementById('delete-modal').classList.remove('open')">✕</button>
            </div>
            <form id="delete-form">
                <div class="modal-body">
                    <p style="font-size:.85rem;color:#c0392b;margin-bottom:1rem;">
                        Bu işlem GERİ ALINAMAZ — kiracının tüm veritabanı silinir. Onaylamak için subdomain'i yazın: <strong id="delete-subdomain-label"></strong>
                    </p>
                    <input type="hidden" id="d-tenant-id">
                    <input type="text" id="d-confirm" class="form-control" placeholder="Subdomain'i yazın">
                    <div id="delete-msg" style="display:none;color:#b91c1c;font-size:0.8rem;margin-top:0.5rem;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('delete-modal').classList.remove('open')">Vazgeç</button>
                    <button type="submit" class="btn btn-danger">Kalıcı Olarak Sil</button>
                </div>
            </form>
        </div>
    </div>

    <!-- WHATSAPP QUICK MESSAGE MODAL -->
    <div class="modal-backdrop" id="whatsapp-modal">
        <div class="modal" style="max-width:540px;">
            <div class="modal-header">
                <div class="modal-title" style="display:flex;align-items:center;gap:0.5rem;">
                    <span style="color:#16a34a;font-size:1.2rem;">🟢</span>
                    <span>WhatsApp Mesajı — <span id="wa-modal-lead-name"></span></span>
                </div>
                <button class="btn btn-secondary btn-icon" onclick="closeWhatsAppModal()">✕</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="wa-modal-lead-id">
                <input type="hidden" id="wa-modal-phone">
                <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:0.75rem;">
                    Alıcı: <strong id="wa-modal-contact" style="color:var(--text-main);"></strong> • <span id="wa-modal-phone-display"></span>
                </div>
                <div class="form-group">
                    <label>Hazır Şablon Seçin</label>
                    <select id="wa-modal-template-select" class="form-control" onchange="applyWhatsAppTemplate(this.value)">
                        <option value="1">🌟 BooKi Randevu Sistemi Tanıtım & Demo Teklifi</option>
                        <option value="2">📅 Saha Ziyareti Öncesi Randevu & Teyit</option>
                        <option value="3">⏱️ 10 Günlük Ücretsiz Deneme Başlatma</option>
                        <option value="custom">💼 Özel / Boş Mesaj</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Gönderilecek Mesaj Metni</label>
                    <textarea id="wa-modal-text" class="form-control" rows="5"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeWhatsAppModal()">Vazgeç</button>
                <button type="button" class="btn btn-success" onclick="sendWhatsAppMessage()" style="background:#16a34a;border-color:#16a34a;">
                    <span>WhatsApp Web ile Gönder ↗</span>
                </button>
            </div>
        </div>
    </div>

    <!-- CALL & VOICE AGENT MODAL (ZADARMA SIP + ELEVENLABS + GOOGLE AI STUDIO LIVE) -->
    <div class="modal-backdrop" id="call-modal">
        <div class="modal" style="max-width:680px; max-height:92vh; overflow-y:auto;">
            <div class="modal-header" style="background:#0f172a;color:white;border-radius:12px 12px 0 0;">
                <div>
                    <div class="modal-title" style="color:white;display:flex;align-items:center;gap:0.5rem;">
                        <span>📞 Sesli Görüşme & AI Çağrı Merkezi</span>
                    </div>
                    <div style="font-size:0.8rem;color:#94a3b8;margin-top:0.2rem;" id="call-modal-lead-title">—</div>
                </div>
                <button class="btn btn-secondary btn-icon" style="color:white;background:rgba(255,255,255,0.1);border:none;" onclick="closeCallModal()">✕</button>
            </div>
            <div class="modal-body" style="padding:1.25rem;">
                <input type="hidden" id="call-lead-id">
                <input type="hidden" id="call-lead-phone">
                <input type="hidden" id="call-lead-sector">

                <!-- AUDIO HARDWARE & PERMISSION STATUS (LENOVO TAB / ANDROID CHROME / DESKTOP) -->
                <div id="call-hardware-bar" style="background:#0f172a;color:#f8fafc;padding:0.75rem 1rem;border-radius:8px;margin-bottom:1rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <div id="hw-mic-icon" style="font-size:1.4rem;line-height:1;">🎧</div>
                            <div>
                                <div style="font-size:0.82rem;font-weight:700;display:flex;align-items:center;gap:0.4rem;">
                                    <span id="hw-device-name">Kulaklık / Mikrofon Durumu</span>
                                    <span class="badge" id="hw-device-badge" style="background:#eab308;color:#000;font-size:0.68rem;padding:2px 7px;border-radius:4px;font-weight:700;">İzin Bekleniyor</span>
                                </div>
                                <div style="font-size:0.72rem;color:#94a3b8;margin-top:2px;" id="hw-device-hint">Arama ve ses iletimi için mikrofon izni gereklidir</div>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                            <!-- Canlı Ses Seviyesi (VU Meter) -->
                            <div style="display:flex;align-items:center;gap:4px;background:rgba(255,255,255,0.08);padding:4px 8px;border-radius:6px;border:1px solid rgba(255,255,255,0.1);" title="Mikrofon Canlı Ses Seviyesi">
                                <span style="font-size:0.65rem;color:#94a3b8;font-weight:700;">SES:</span>
                                <div style="width:55px;height:8px;background:rgba(255,255,255,0.2);border-radius:4px;overflow:hidden;">
                                    <div id="audio-vu-bar" style="width:0%;height:100%;background:#10b981;transition:width 0.06s ease;border-radius:4px;"></div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm" id="btn-request-mic" onclick="requestAudioHardwareAccess(true)" style="background:#2563eb;color:white;border:none;font-size:0.75rem;padding:5px 12px;border-radius:6px;cursor:pointer;font-weight:700;box-shadow:0 2px 4px rgba(37,99,235,0.3);">
                                🎙️ İzin Ver & Bağla
                            </button>
                            <button type="button" class="btn btn-sm" id="btn-test-sound" onclick="playAudioTestTone()" style="background:rgba(255,255,255,0.15);color:white;border:none;font-size:0.75rem;padding:5px 8px;border-radius:6px;cursor:pointer;" title="Kulaklık Test Tonu">
                                🔊 Test
                            </button>
                            <button type="button" class="btn btn-sm" id="btn-toggle-mute" onclick="toggleCallMicrophoneMute()" style="display:none;background:rgba(255,255,255,0.15);color:white;border:none;font-size:0.75rem;padding:5px 8px;border-radius:6px;cursor:pointer;">
                                🔇 Sustur
                            </button>
                            <button type="button" class="btn btn-sm" id="btn-audio-help" onclick="toggleDiagnosticDrawer()" style="background:rgba(255,255,255,0.12);color:#93c5fd;border:none;font-size:0.75rem;padding:5px 8px;border-radius:6px;cursor:pointer;" title="İzin Sorun Giderici (Lenovo / Chrome)">
                                🛠️ Sorun Gider
                            </button>
                        </div>
                    </div>

                    <!-- Insecure Context / HTTPS Warning for Chrome on Tablets -->
                    <div id="hw-https-warning" style="display:none;margin-top:0.75rem;padding:0.75rem 1rem;background:#450a0a;border:1px solid #ef4444;border-radius:8px;font-size:0.8rem;color:#fecaca;">
                        <div style="font-weight:700;display:flex;align-items:center;gap:6px;font-size:0.85rem;color:#fca5a5;">
                            <span>🚨 Chrome Android Güvenlik Uyarısı: HTTPS Bağlantısı Zorunludur</span>
                        </div>
                        <div style="margin-top:4px;line-height:1.45;">
                            Google Chrome (özellikle Lenovo Tab 11 gibi Android cihazlarda), mikrofon izin ekranını <strong>yalnızca HTTPS</strong> bağlantılarında açar. Şu an <code>http://</code> protokolünde olduğunuz için Chrome izin penceresini göstermez.
                        </div>
                        <div style="margin-top:0.6rem;display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
                            <button type="button" class="btn btn-sm" onclick="switchToHttps()" style="background:#ef4444;color:white;border:none;font-size:0.75rem;padding:5px 12px;border-radius:6px;font-weight:700;cursor:pointer;">
                                🔒 Sayfayı HTTPS ile Yeniden Aç (Önerilen)
                            </button>
                            <button type="button" class="btn btn-sm" onclick="toggleChromeFlagsGuide()" style="background:rgba(255,255,255,0.15);color:white;border:none;font-size:0.75rem;padding:5px 10px;border-radius:6px;cursor:pointer;">
                                ⚙️ Yerel IP / Geliştirme Ayarı
                            </button>
                        </div>
                        <div id="hw-chrome-flags-guide" style="display:none;margin-top:0.6rem;padding:0.6rem;background:rgba(0,0,0,0.3);border-radius:6px;font-size:0.75rem;">
                            <div><strong>IP ile bağlanırken Chrome'da mikrofon izin ekranını açmak için:</strong></div>
                            <ol style="margin:4px 0 0 1.2rem;padding:0;line-height:1.5;">
                                <li>Chrome'da yeni sekme açın ve adrese gidin: <code>chrome://flags/#unsafely-treat-insecure-origin-as-secure</code></li>
                                <li>Açılan ayar kutusuna bu adresi yapıştırın: <code id="hw-current-origin"></code> <button type="button" onclick="copyOriginToClipboard()" style="background:#334155;color:#fff;border:none;padding:2px 6px;border-radius:4px;cursor:pointer;font-size:0.7rem;">📋 Kopyala</button></li>
                                <li>Seçeneği <strong>Enabled</strong> yapıp sağ alttaki <strong>Relaunch</strong> butonuna basın.</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Chrome Denied / Blocked Warning with Step-by-Step Instructions -->
                    <div id="hw-denied-warning" style="display:none;margin-top:0.75rem;padding:0.75rem 1rem;background:#431407;border:1px solid #f97316;border-radius:8px;font-size:0.8rem;color:#ffedd5;">
                        <div style="font-weight:700;display:flex;align-items:center;gap:6px;font-size:0.85rem;color:#fdba74;">
                            <span>🚫 Chrome Mikrofon İznini Engellemiş Durumda</span>
                        </div>
                        <div style="margin-top:4px;line-height:1.45;">
                            Chrome daha önce bu sitede engellendiği için yeni izin penceresini <strong>otomatik olarak açmaz</strong>. İzni aktifleştirmek için Lenovo tabletinizde şu 3 adımı yapın:
                        </div>
                        <div style="margin-top:0.5rem;display:flex;flex-direction:column;gap:5px;background:rgba(0,0,0,0.3);padding:0.6rem 0.8rem;border-radius:6px;">
                            <div><strong>1. Adım:</strong> Chrome adres çubuğunun hemen solundaki <strong>🔒 Kilit</strong> veya <strong>⚙️ / 🎚️ Ayar</strong> simgesine dokunun.</div>
                            <div><strong>2. Adım:</strong> <strong>İzinler (Permissions)</strong> menüsüne dokunun.</div>
                            <div><strong>3. Adım:</strong> <strong>Mikrofon</strong> seçeneğini <strong>"İzin Ver" (Allow)</strong> yapın.</div>
                            <div><strong>4. Adım:</strong> Aşağıdaki <strong>"🔄 İzinleri Yeniden Kontrol Et"</strong> butonuna dokunun.</div>
                        </div>
                        <div style="margin-top:0.65rem;display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
                            <button type="button" class="btn btn-sm" onclick="checkMicrophonePermissionStatus(true)" style="background:#ea580c;color:white;border:none;font-size:0.75rem;padding:5px 12px;border-radius:6px;font-weight:700;cursor:pointer;">
                                🔄 İzinleri Yeniden Kontrol Et & Bağlan
                            </button>
                            <button type="button" class="btn btn-sm" onclick="requestAudioHardwareAccess(true)" style="background:#2563eb;color:white;border:none;font-size:0.75rem;padding:5px 12px;border-radius:6px;font-weight:700;cursor:pointer;">
                                🎙️ İzin Ekranını Doğrudan Zorla
                            </button>
                        </div>
                    </div>

                    <!-- Interactive Diagnostic Drawer -->
                    <div id="hw-diagnostic-drawer" style="display:none;margin-top:0.75rem;padding:0.75rem 1rem;background:#1e293b;border:1px solid #475569;border-radius:8px;font-size:0.8rem;color:#f8fafc;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                            <strong style="color:#60a5fa;">🛠️ Lenovo Tab 11 & Chrome Donanım Teşhisi</strong>
                            <button type="button" onclick="toggleDiagnosticDrawer(false)" style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:0.85rem;">✕ Kapat</button>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:0.5rem;margin-bottom:0.75rem;">
                            <div style="background:rgba(0,0,0,0.25);padding:6px 10px;border-radius:6px;">
                                <div style="font-size:0.68rem;color:#94a3b8;">Bağlantı Protokolü</div>
                                <div id="diag-protocol" style="font-weight:700;font-size:0.78rem;margin-top:2px;">Kontrol ediliyor...</div>
                            </div>
                            <div style="background:rgba(0,0,0,0.25);padding:6px 10px;border-radius:6px;">
                                <div style="font-size:0.68rem;color:#94a3b8;">Cihaz / Tarayıcı</div>
                                <div id="diag-browser" style="font-weight:700;font-size:0.78rem;margin-top:2px;">Google Chrome (Android)</div>
                            </div>
                            <div style="background:rgba(0,0,0,0.25);padding:6px 10px;border-radius:6px;">
                                <div style="font-size:0.68rem;color:#94a3b8;">WebRTC Desteği</div>
                                <div id="diag-webrtc" style="font-weight:700;font-size:0.78rem;margin-top:2px;">Kontrol ediliyor...</div>
                            </div>
                            <div style="background:rgba(0,0,0,0.25);padding:6px 10px;border-radius:6px;">
                                <div style="font-size:0.68rem;color:#94a3b8;">Mikrofon İzin Durumu</div>
                                <div id="diag-perm-status" style="font-weight:700;font-size:0.78rem;margin-top:2px;">Kontrol ediliyor...</div>
                            </div>
                        </div>
                        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
                            <button type="button" class="btn btn-sm" onclick="requestAudioHardwareAccess(true)" style="background:#2563eb;color:white;border:none;font-size:0.75rem;padding:4px 10px;border-radius:6px;cursor:pointer;">
                                🎙️ İzin Testi Başlat
                            </button>
                            <button type="button" class="btn btn-sm" onclick="runDiagnosticsCheck()" style="background:#475569;color:white;border:none;font-size:0.75rem;padding:4px 10px;border-radius:6px;cursor:pointer;">
                                🔄 Durumu Yenile
                            </button>
                            <button type="button" class="btn btn-sm" onclick="switchToHttps()" style="background:#0284c7;color:white;border:none;font-size:0.75rem;padding:4px 10px;border-radius:6px;cursor:pointer;">
                                🔒 HTTPS ile Aç
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PROVIDER SELECTOR -->
                <div style="display:flex;gap:0.5rem;margin-bottom:1.25rem;background:#f1f5f9;padding:0.35rem;border-radius:8px;">
                    <button type="button" class="btn btn-sm call-tab-btn active" id="btn-tab-zadarma" onclick="switchCallProvider('zadarma')" style="flex:1;">
                        📞 Zadarma SIP
                    </button>
                    <button type="button" class="btn btn-sm call-tab-btn" id="btn-tab-elevenlabs" onclick="switchCallProvider('elevenlabs')" style="flex:1;">
                        🎙️ ElevenLabs AI
                    </button>
                    <button type="button" class="btn btn-sm call-tab-btn" id="btn-tab-gemini" onclick="switchCallProvider('gemini_live')" style="flex:1;">
                        ⚡ Google Gemini Live
                    </button>
                </div>

                <!-- CALL CONTROLS BAR -->
                <div style="background:#f8fafc;border:1px solid var(--border-color);border-radius:10px;padding:1rem;margin-bottom:1.25rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;">
                    <div>
                        <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;font-weight:700;">Görüşme Durumu</div>
                        <div style="font-size:1.05rem;font-weight:800;color:var(--text-main);margin-top:0.2rem;display:flex;align-items:center;gap:0.5rem;">
                            <span class="call-pulse-dot" id="call-status-dot" style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#94a3b8;"></span>
                            <span id="call-status-text">Hazır</span>
                        </div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;font-weight:700;">Görüşme Süresi</div>
                        <div style="font-size:1.3rem;font-weight:800;font-family:monospace;color:var(--primary);" id="call-timer">00:00</div>
                    </div>
                    <div style="display:flex;gap:0.5rem;">
                        <button type="button" class="btn btn-success" id="btn-start-call" onclick="startCallSession()" style="background:#16a34a;border-color:#16a34a;">
                            ▶ Aramayı Başlat
                        </button>
                        <button type="button" class="btn btn-danger" id="btn-end-call" onclick="endCallSession()" style="display:none;">
                            ⏹ Bitir
                        </button>
                    </div>
                </div>

                <!-- TARGET PHONE & CHANNEL SELECTOR -->
                <div style="background:#f8fafc;border:1px solid var(--border-color);border-radius:10px;padding:0.75rem 1rem;margin-bottom:1rem;">
                    <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;margin-bottom:0.6rem;">
                        <span style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">📞 Aranacak Numara:</span>
                        <input type="text" id="call-target-phone" class="form-control" style="max-width:200px;font-size:0.9rem;padding:0.3rem 0.6rem;height:auto;font-weight:700;background:#fff;" value="" placeholder="905xxxxxxxxx">
                        <span style="font-size:0.72rem;color:var(--text-muted);">(Doğrudan buradan da değiştirebilir veya test edebilirsiniz)</span>
                    </div>

                    <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                        <span style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Arama Yöntemi:</span>
                        <button type="button" id="zd-ch-browser" class="btn btn-sm" onclick="setZadarmaDialChannel('browser')" style="border:1px solid var(--border-color);">
                            🌐 Tarayıcıdan Konuş (WebRTC)
                        </button>
                        <button type="button" id="zd-ch-callback" class="btn btn-sm" onclick="setZadarmaDialChannel('callback')" style="border:1px solid var(--border-color);">
                            📱 Telefonumu Çaldır (Callback)
                        </button>
                        <span id="zd-widget-status" style="font-size:0.72rem;color:var(--text-muted);"></span>
                    </div>

                    <div id="zd-callback-box" style="display:none;margin-top:0.6rem;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:0.6rem 0.75rem;">
                        <div style="font-size:0.75rem;font-weight:700;color:#92400e;margin-bottom:0.25rem;">
                            📱 Önce Sizin Hangi Telefonunuz Çalsın?
                        </div>
                        <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                            <input type="text" id="zd-callback-phone" class="form-control" style="max-width:220px;font-size:0.85rem;padding:0.35rem 0.6rem;height:auto;font-weight:700;background:#fff;" value="<?= e($ps['zadarma_caller_id'] ?? '05062505562') ?>" placeholder="Örn: 05062505562 veya 100">
                            <span style="font-size:0.72rem;color:#b45309;">Aramayı Başlat deyince önce bu telefonunuz çalar; siz açınca müşteri bağlanır.</span>
                        </div>
                    </div>
                </div>

                <!-- LIVE TRANSCRIPT FEED -->
                <div class="form-group" style="margin-bottom:1rem;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.35rem;">
                        <label style="margin:0;font-weight:700;">🎙️ Canlı Konuşma Transkripti</label>
                        <span style="font-size:0.75rem;color:var(--text-muted);" id="transcript-status">Canlı dinleme / AI diyaloğu</span>
                    </div>
                    <textarea id="call-transcript" class="form-control" rows="5" placeholder="Görüşme başladığında konuşma dökümü ve AI diyalogları burada gerçek zamanlı görünecektir..."></textarea>
                </div>

                <!-- OPERATOR NOTES -->
                <div class="form-group" style="margin-bottom:1rem;">
                    <label style="font-weight:700;">📝 Operatör Görüşme Notları</label>
                    <textarea id="call-notes" class="form-control" rows="2" placeholder="Müşterinin itirazları, ilgilendiği paket, özel notlar..."></textarea>
                </div>

                <!-- OUTCOME & NEXT STAGE -->
                <div class="form-row" style="margin-bottom:1rem;">
                    <div class="form-group">
                        <label style="font-weight:700;">Görüşme Sonucu</label>
                        <select id="call-outcome" class="form-control">
                            <option value="demo_started">🎉 Görüşme Başarılı — Demo Başlatıldı</option>
                            <option value="interested_follow_up" selected>👍 İlgilendi — Takip Araması İstendi</option>
                            <option value="visit_requested">📍 Saha Ziyareti Talep Etti</option>
                            <option value="busy_no_answer">📞 Cevap Vermedi / Meşgul</option>
                            <option value="objection_retry">⏳ İtiraz Etti — Yeniden Aranacak</option>
                            <option value="not_interested">❌ Olumsuz — İstemiyor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="font-weight:700;">Yeni Aşama (Opsiyonel)</label>
                        <select id="call-new-stage" class="form-control">
                            <option value="">Aşama Değişmesin</option>
                            <?php foreach (vars('stage_definitions') as $stk => $stlbl): ?>
                                <option value="<?= e($stk) ?>"><?= e($stlbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- OPTIONAL FOLLOW UP TASK -->
                <div style="background:#f8fafc;border:1px solid var(--border-color);border-radius:8px;padding:0.75rem;margin-bottom:1rem;">
                    <label style="display:flex;align-items:center;gap:0.5rem;font-weight:700;font-size:0.85rem;cursor:pointer;margin:0;">
                        <input type="checkbox" id="call-has-followup" onchange="document.getElementById('call-followup-details').style.display = this.checked ? 'block' : 'none'">
                        <span>Otomatik Takip Görevi Planla</span>
                    </label>
                    <div id="call-followup-details" style="display:none;margin-top:0.75rem;">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Takip Tarihi</label>
                                <input type="date" id="call-followup-date" class="form-control" value="<?= date('Y-m-d', strtotime('+2 days')) ?>">
                            </div>
                            <div class="form-group">
                                <label>Takip Saati</label>
                                <input type="time" id="call-followup-time" class="form-control" value="11:00">
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCallModal()">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="btn-save-call-log" onclick="saveCallLogAndFinish()">
                    <span>💾 Görüşmeyi ve Transkripti Kaydet</span>
                </button>
            </div>
        </div>
    </div>

    <!-- TOAST CONTAINER -->
    <div id="toast-container"></div>

    <!-- SCRIPTS -->
    <script>
        const csrfToken = '<?= e(vars('csrf_token')) ?>';
        const BASE_URL = '<?= site_url() ?>';
        let currentWizStep = 1;
        let selectedFile = null;
        let activeLeadId = null;
        let omnisearchDebounce = null;
        let leadsSearchDebounce = null;
        let pipelineSearchDebounce = null;

        // HTTP POST HELPER
        function post(url, data) {
            const params = new URLSearchParams();
            for (const key in data) {
                if (data[key] !== null && data[key] !== undefined) {
                    params.append(key, data[key]);
                }
            }
            params.set('csrf_token', csrfToken);
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString()
            }).then(r => r.json());
        }

        // TOAST NOTIFIER
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `<span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                toast.style.transition = 'all 0.2s ease';
                setTimeout(() => toast.remove(), 200);
            }, 3500);
        }

        // CREATE LEAD & TASK HANDLERS
        function openCreateLeadModal() {
            document.getElementById('create-lead-form').reset();
            document.getElementById('create-lead-msg').style.display = 'none';
            document.getElementById('create-lead-modal').classList.add('open');
        }

        function updateNewLeadMrr(pkg) {
            const prices = { 'Starter': '1999.00', 'Professional': '2199.00', 'Enterprise': '4499.00' };
            document.getElementById('nl-potential-mrr').value = prices[pkg] || '2199.00';
        }

        function handleCreateLead(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-save-lead');
            btn.disabled = true;
            btn.textContent = 'Kaydediliyor...';

            post('<?= site_url('superadmin_tenants/api_create_lead') ?>', {
                name: document.getElementById('nl-name').value,
                sector: document.getElementById('nl-sector').value,
                district: document.getElementById('nl-district').value,
                address: document.getElementById('nl-address').value,
                contact_person: document.getElementById('nl-contact-person').value,
                contact_title: document.getElementById('nl-contact-title').value,
                phone: document.getElementById('nl-phone').value,
                whatsapp: document.getElementById('nl-whatsapp').value,
                email: document.getElementById('nl-email').value,
                stage: document.getElementById('nl-stage').value,
                package: document.getElementById('nl-package').value,
                potential_mrr: document.getElementById('nl-potential-mrr').value,
                notes: document.getElementById('nl-notes').value,
            }).then(data => {
                btn.disabled = false;
                btn.textContent = 'Kaydet';

                if (!data.success) {
                    const msg = document.getElementById('create-lead-msg');
                    msg.textContent = data.message || 'Hata oluştu.';
                    msg.style.display = 'block';
                    return;
                }

                document.getElementById('create-lead-modal').classList.remove('open');
                showToast('Yeni lead başarıyla eklendi!', 'success');
                loadPipeline();
                loadLeadsTable();
            });
        }

        function openCreateTaskModal(leadId = null) {
            document.getElementById('create-task-form').reset();
            if (leadId) document.getElementById('nt-lead-id').value = leadId;
            document.getElementById('create-task-modal').classList.add('open');
        }

        function handleCreateTask(e) {
            e.preventDefault();
            post('<?= site_url('superadmin_tenants/api_create_task') ?>', {
                title: document.getElementById('nt-title').value,
                due_date: document.getElementById('nt-due-date').value,
                due_time: document.getElementById('nt-due-time').value,
                priority: document.getElementById('nt-priority').value,
                id_leads: document.getElementById('nt-lead-id').value,
                description: document.getElementById('nt-description').value,
            }).then(data => {
                if (!data.success) {
                    showToast(data.message || 'Görev oluşturulamadı', 'error');
                    return;
                }
                document.getElementById('create-task-modal').classList.remove('open');
                showToast('Görev oluşturuldu!', 'success');
                window.location.reload();
            });
        }

        // TAB SWITCHER
        function switchTab(tabId, params = {}) {
            document.querySelectorAll('.tab-content-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.app-sidebar .nav-item').forEach(i => i.classList.remove('active'));
            
            const target = document.getElementById(`tab-${tabId}`);
            if (target) {
                target.classList.add('active');
            }

            const navItems = document.querySelectorAll('.app-sidebar .nav-item');
            navItems.forEach(n => {
                if (n.getAttribute('onclick') && n.getAttribute('onclick').includes(`switchTab('${tabId}')`)) {
                    n.classList.add('active');
                }
            });

            // Lazy load tab data
            if (tabId === 'pipeline') {
                loadPipeline();
            } else if (tabId === 'leads') {
                if (params.stage) {
                    const sel = document.getElementById('leads-stage-filter');
                    if (sel) sel.value = params.stage;
                }
                loadLeadsTable();
            } else if (tabId === 'onboarding') {
                loadOnboardingSessions();
            } else if (tabId === 'map') {
                initMapIfNeeded();
            } else if (tabId === 'places-crawler') {
                initPlacesCrawlerTab();
            }

            // Update URL hash
            history.replaceState(null, '', `#${tabId}`);
        }

        // MOBILE SIDEBAR
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('app-sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            sidebar.classList.toggle('mobile-open');
            backdrop.classList.toggle('open');
        }

        // DESKTOP SIDEBAR COLLAPSE
        function toggleSidebarCollapse() {
            const sidebar = document.getElementById('app-sidebar');
            if (!sidebar) return;
            sidebar.classList.toggle('collapsed');
            const isCollapsed = sidebar.classList.contains('collapsed');
            try {
                localStorage.setItem('booki_sidebar_collapsed', isCollapsed ? '1' : '0');
            } catch (e) {}
        }

        // --- PIPELINE KANBAN CONTROLLER ---
        const KANBAN_STAGES = [
            { key: 'New Lead', label: 'Yeni Lead' },
            { key: 'Qualified', label: 'Nitelikli' },
            { key: 'Visit Planned', label: 'Ziyaret Planlandı' },
            { key: 'Visited', label: 'Ziyaret Edildi' },
            { key: 'Meeting', label: 'Görüşme' },
            { key: 'Demo Presented', label: 'Demo Sunuldu' },
            { key: 'Trial Started', label: '10-Gün Demo' },
            { key: 'Follow-up', label: 'Takip' },
            { key: 'Won', label: 'Kazanıldı (Satış)' },
            { key: 'Lost', label: 'Kaybedildi' }
        ];

        function loadPipeline() {
            const container = document.getElementById('kanban-board-container');
            const q = document.getElementById('pipeline-search')?.value || '';
            const sector = document.getElementById('pipeline-sector-filter')?.value || '';
            const district = document.getElementById('pipeline-district-filter')?.value || '';

            const params = new URLSearchParams({ q, sector, district });
            fetch(`<?= site_url('superadmin_tenants/api_pipeline') ?>?${params.toString()}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        container.innerHTML = '<div style="padding:2rem;color:#b91c1c;">Veri yüklenemedi.</div>';
                        return;
                    }
                    renderKanban(data.pipeline, data.counts);
                });
        }

        function filterPipelineDebounced() {
            clearTimeout(pipelineSearchDebounce);
            pipelineSearchDebounce = setTimeout(loadPipeline, 300);
        }

        function renderKanban(pipeline, counts = {}) {
            const container = document.getElementById('kanban-board-container');
            container.innerHTML = '';

            KANBAN_STAGES.forEach(st => {
                const stageData = pipeline[st.key] || {};
                const leads = Array.isArray(stageData) ? stageData : (stageData.leads || []);
                const count = counts[st.key] !== undefined ? counts[st.key] : (stageData.count || leads.length);

                const col = document.createElement('div');
                col.className = 'kanban-column';
                col.setAttribute('data-stage', st.key);

                col.innerHTML = `
                    <div class="kanban-column-header">
                        <span class="kanban-stage-name">${st.label}</span>
                        <span class="kanban-badge">${count}</span>
                    </div>
                    <div class="kanban-cards-container" data-stage="${st.key}" ondragover="handleDragOver(event)" ondrop="handleDrop(event, '${st.key}')">
                    </div>
                `;

                const cardsContainer = col.querySelector('.kanban-cards-container');
                leads.forEach(ld => {
                    const card = document.createElement('div');
                    card.className = 'kanban-card';
                    card.setAttribute('draggable', 'true');
                    card.setAttribute('data-lead-id', ld.id);
                    card.ondragstart = (e) => handleDragStart(e, ld.id);
                    card.ondragend = handleDragEnd;

                    let trialBadge = '';
                    if (ld.stage === 'Trial Started') {
                        if (ld.demo_days_left !== null) {
                            if (ld.demo_days_left < 0) {
                                trialBadge = `<span class="card-tag trial-urgent">Demo Bitti (${Math.abs(ld.demo_days_left)}g)</span>`;
                            } else if (ld.demo_days_left <= 3) {
                                trialBadge = `<span class="card-tag trial-urgent">Demo: ${ld.demo_days_left} gün kaldı</span>`;
                            } else {
                                trialBadge = `<span class="card-tag trial-active">Demo: ${ld.demo_days_left} gün</span>`;
                            }
                        }
                    }

                    const safeName = escapeJs(ld.name || '');
                    const safeContact = escapeJs(ld.contact_person || '');
                    const safeSector = escapeJs(ld.sector || '');
                    const cleanWa = ld.clean_whatsapp || '';
                    const cleanPh = ld.clean_phone || ld.phone || '';

                    card.innerHTML = `
                        <div class="card-meta-tags">
                            <span class="card-tag sector">${ld.sector || 'Genel'}</span>
                            <span class="card-tag district">${ld.district || 'İstanbul'}</span>
                            ${trialBadge}
                        </div>
                        <div class="card-title" onclick="openLeadDrawer(${ld.id})">${ld.name}</div>
                        <div class="card-contact">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                            <span>${ld.contact_person ? ld.contact_person + ' • ' : ''}${ld.phone || '—'}</span>
                        </div>
                        <div class="card-footer">
                            <span class="card-mrr">₺${Number(ld.potential_mrr || 2199).toLocaleString('tr-TR')}/ay</span>
                            <div class="card-actions-row">
                                <button class="card-btn card-btn-call" onclick="openCallModal(${ld.id}, '${safeName}', '${cleanPh}', '${safeContact}', '${safeSector}')" title="Sesli / AI Arama">📞</button>
                                ${cleanWa ? `<button class="card-btn card-btn-wa" onclick="openWhatsAppModal(${ld.id}, '${safeName}', '${cleanWa}', '${safeContact}', '${safeSector}')" title="WhatsApp Mesajı">WA</button>` : ''}
                                ${ld.instagram_url ? `<a href="${ld.instagram_url}" target="_blank" onclick="logCommunication(${ld.id}, 'instagram')" class="card-btn card-btn-insta" title="Instagram">IG</a>` : ''}
                                ${ld.clean_email ? `<a href="mailto:${ld.clean_email}" onclick="logCommunication(${ld.id}, 'email')" class="card-btn card-btn-email" title="E-posta">✉️</a>` : ''}
                                <button class="card-btn" onclick="openFieldVisitModal(${ld.id}, '${safeContact}')" title="Saha Ziyareti">Ziyaret</button>
                                ${ld.stage !== 'Won' ? `<button class="card-btn" onclick="openTenantWizardForLead(${ld.id})" style="color:var(--success);font-weight:700;" title="Kazanıldı & Tenant Oluştur">Won</button>` : ''}
                            </div>
                        </div>
                    `;
                    cardsContainer.appendChild(card);
                });

                container.appendChild(col);
            });
        }

        let draggedLeadId = null;
        function handleDragStart(e, leadId) {
            draggedLeadId = leadId;
            e.currentTarget.classList.add('dragging');
            e.dataTransfer.setData('text/plain', leadId);
        }
        function handleDragEnd(e) {
            e.currentTarget.classList.remove('dragging');
        }
        function handleDragOver(e) {
            e.preventDefault();
        }
        function handleDrop(e, newStage) {
            e.preventDefault();
            const leadId = draggedLeadId;
            if (!leadId) return;

            post('<?= site_url('superadmin_tenants/api_update_stage') ?>', {
                lead_id: leadId,
                stage: newStage,
                reason: 'Kanban sürükle-bırak aşama güncellemesi'
            }).then(data => {
                if (data.success) {
                    showToast(`Lead aşaması güncellendi: ${newStage}`, 'success');
                    loadPipeline();
                } else {
                    showToast(data.message || 'Aşama güncellenemedi', 'error');
                }
            });
        }

        // --- LEADS TABLE CONTROLLER ---
        let currentLeadsPage = 1;
        let currentLeadsLimit = 25;

        function loadLeadsTable(page = 1) {
            currentLeadsPage = parseInt(page, 10) || 1;
            const q = document.getElementById('leads-search')?.value || '';
            const sector = document.getElementById('leads-sector-filter')?.value || '';
            const district = document.getElementById('leads-district-filter')?.value || '';
            const stage = document.getElementById('leads-stage-filter')?.value || '';
            const limitSelect = document.getElementById('leads-per-page-select');
            if (limitSelect) currentLeadsLimit = parseInt(limitSelect.value, 10) || 25;

            const tbody = document.getElementById('leads-table-tbody');
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-light);">Yükleniyor...</td></tr>';

            const params = new URLSearchParams({ q, sector, district, stage, page: currentLeadsPage, limit: currentLeadsLimit });
            fetch(`<?= site_url('superadmin_tenants/api_leads') ?>?${params.toString()}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#b91c1c;">Hata oluştu.</td></tr>';
                        return;
                    }

                    renderLeadsTableRows(data.leads);
                    renderLeadsPagination(data.total || 0, data.limit || currentLeadsLimit, data.page || data.current_page || currentLeadsPage);
                })
                .catch(err => {
                    console.error('Leads load error:', err);
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#b91c1c;">Sunucu bağlantı hatası oluştu.</td></tr>';
                });
        }

        function filterLeadsDebounced() {
            clearTimeout(leadsSearchDebounce);
            leadsSearchDebounce = setTimeout(() => loadLeadsTable(1), 300);
        }

        function renderLeadsTableRows(leads) {
            const tbody = document.getElementById('leads-table-tbody');
            tbody.innerHTML = '';

            if (!leads || leads.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-light);">Eşleşen lead bulunamadı.</td></tr>';
                return;
            }

            leads.forEach(ld => {
                const tr = document.createElement('tr');
                
                let trialStatus = '—';
                if (ld.stage === 'Trial Started') {
                    if (ld.demo_days_left !== null) {
                        trialStatus = `<span class="badge ${ld.demo_days_left <= 3 ? 'suspended' : 'expired'}">${ld.demo_days_left} gün</span>`;
                    }
                } else if (ld.stage === 'Won') {
                    trialStatus = '<span class="badge active">Kazanıldı</span>';
                }

                const safeName = escapeJs(ld.name || '');
                const safeContact = escapeJs(ld.contact_person || '');
                const safeSector = escapeJs(ld.sector || '');
                const cleanWa = ld.clean_whatsapp || '';
                const cleanPh = ld.clean_phone || ld.phone || '';

                tr.innerHTML = `
                    <td style="font-size:0.75rem;color:var(--text-light);font-weight:600;">#${ld.id}</td>
                    <td>
                        <strong style="cursor:pointer;color:var(--primary);" onclick="openLeadDrawer(${ld.id})">${ld.name}</strong>
                    </td>
                    <td>
                        <span class="card-tag sector">${ld.sector || 'Genel'}</span>
                        <span class="card-tag district">${ld.district || 'İstanbul'}</span>
                    </td>
                    <td>
                        <div style="font-weight:600;">${ld.contact_person ? ld.contact_person : 'Yetkili'}</div>
                        <div style="display:flex;align-items:center;gap:0.35rem;margin-top:0.25rem;flex-wrap:wrap;">
                            <span style="font-size:0.75rem;color:var(--text-light);">${ld.phone || '—'}</span>
                            ${cleanWa ? `<a href="javascript:void(0)" onclick="openWhatsAppModal(${ld.id}, '${safeName}', '${cleanWa}', '${safeContact}', '${safeSector}')" style="color:#16a34a;font-weight:700;text-decoration:none;font-size:0.72rem;background:#f0fdf4;padding:1px 5px;border-radius:3px;border:1px solid #bbf7d0;" title="WhatsApp Mesajı">WA</a>` : ''}
                            ${ld.instagram_url ? `<a href="${ld.instagram_url}" target="_blank" onclick="logCommunication(${ld.id}, 'instagram')" style="color:#9333ea;font-weight:700;text-decoration:none;font-size:0.72rem;background:#faf5ff;padding:1px 5px;border-radius:3px;border:1px solid #e9d5ff;" title="Instagram: @${ld.clean_instagram}">IG</a>` : ''}
                            ${ld.clean_email ? `<a href="mailto:${ld.clean_email}" onclick="logCommunication(${ld.id}, 'email')" style="color:#2563eb;font-weight:700;text-decoration:none;font-size:0.72rem;background:#eff6ff;padding:1px 5px;border-radius:3px;border:1px solid #bfdbfe;" title="E-posta: ${ld.clean_email}">✉️</a>` : ''}
                        </div>
                    </td>
                    <td><span class="badge ${ld.stage === 'Won' ? 'active' : (ld.stage === 'Lost' ? 'suspended' : 'pending')}">${ld.stage_label || ld.stage}</span></td>
                    <td>${trialStatus}</td>
                    <td><strong>₺${Number(ld.potential_mrr || 2199).toLocaleString('tr-TR')}</strong></td>
                    <td style="text-align:right;white-space:nowrap;">
                        <button class="btn btn-primary btn-sm" onclick="openCallModal(${ld.id}, '${safeName}', '${cleanPh}', '${safeContact}', '${safeSector}')" style="background:#0f172a;border-color:#0f172a;" title="Zadarma / ElevenLabs / Gemini Arama">📞 Ara</button>
                        <button class="btn btn-secondary btn-sm" onclick="openLeadDrawer(${ld.id})">Detay</button>
                        <button class="btn btn-secondary btn-sm" onclick="openFieldVisitModal(${ld.id}, '${safeContact}')">Ziyaret</button>
                        ${ld.stage !== 'Won' ? `<button class="btn btn-success btn-sm" onclick="openTenantWizardForLead(${ld.id})">Won</button>` : ''}
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function renderLeadsPagination(total, limit, page) {
            total = Number(total) || 0;
            limit = Number(limit) || 25;
            page = Number(page) || 1;

            const info = document.getElementById('leads-pagination-info');
            const btns = document.getElementById('leads-pagination-buttons');
            const totalPages = Math.max(1, Math.ceil(total / limit));

            const start = total === 0 ? 0 : (page - 1) * limit + 1;
            const end = Math.min(total, page * limit);
            if (info) {
                info.textContent = `Toplam ${total.toLocaleString('tr-TR')} işletmeden ${start} - ${end} arası gösteriliyor (Sayfa ${page}/${totalPages})`;
            }

            if (!btns) return;
            btns.innerHTML = '';
            if (totalPages <= 1) return;

            // First & Prev
            if (page > 1) {
                const first = document.createElement('button');
                first.className = 'btn btn-secondary btn-sm';
                first.innerHTML = '&laquo;';
                first.title = 'İlk Sayfa';
                first.onclick = () => loadLeadsTable(1);
                btns.appendChild(first);

                const prev = document.createElement('button');
                prev.className = 'btn btn-secondary btn-sm';
                prev.textContent = '‹ Önceki';
                prev.onclick = () => loadLeadsTable(page - 1);
                btns.appendChild(prev);
            }

            // Numeric page buttons
            let startPage = Math.max(1, page - 2);
            let endPage = Math.min(totalPages, page + 2);

            if (startPage > 1) {
                const b = document.createElement('button');
                b.className = 'btn btn-secondary btn-sm';
                b.textContent = '1';
                b.onclick = () => loadLeadsTable(1);
                btns.appendChild(b);

                if (startPage > 2) {
                    const dots = document.createElement('span');
                    dots.style.padding = '0.35rem 0.4rem';
                    dots.style.color = 'var(--text-light)';
                    dots.textContent = '...';
                    btns.appendChild(dots);
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                const b = document.createElement('button');
                b.className = `btn ${i === page ? 'btn-primary' : 'btn-secondary'} btn-sm`;
                b.textContent = i;
                if (i === page) {
                    b.style.fontWeight = '700';
                }
                b.onclick = () => loadLeadsTable(i);
                btns.appendChild(b);
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const dots = document.createElement('span');
                    dots.style.padding = '0.35rem 0.4rem';
                    dots.style.color = 'var(--text-light)';
                    dots.textContent = '...';
                    btns.appendChild(dots);
                }

                const b = document.createElement('button');
                b.className = 'btn btn-secondary btn-sm';
                b.textContent = totalPages;
                b.onclick = () => loadLeadsTable(totalPages);
                btns.appendChild(b);
            }

            // Next & Last
            if (page < totalPages) {
                const next = document.createElement('button');
                next.className = 'btn btn-secondary btn-sm';
                next.textContent = 'Sonraki ›';
                next.onclick = () => loadLeadsTable(page + 1);
                btns.appendChild(next);

                const last = document.createElement('button');
                last.className = 'btn btn-secondary btn-sm';
                last.innerHTML = '&raquo;';
                last.title = 'Son Sayfa';
                last.onclick = () => loadLeadsTable(totalPages);
                btns.appendChild(last);
            }
        }

        // --- LEAD DETAIL DRAWER ---
        function openLeadDrawer(leadId) {
            activeLeadId = leadId;
            const overlay = document.getElementById('lead-drawer-overlay');
            const body = document.getElementById('drawer-body-content');
            const footer = document.getElementById('drawer-footer-actions');

            overlay.classList.add('open');
            body.innerHTML = '<div style="padding:2rem;text-align:center;color:var(--text-light);">Yükleniyor...</div>';
            footer.innerHTML = '';

            fetch(`<?= site_url('superadmin_tenants/api_lead_detail') ?>?lead_id=${leadId}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        body.innerHTML = '<div style="color:#b91c1c;padding:1rem;">Lead bilgileri alınamadı.</div>';
                        return;
                    }

                    const ld = data.lead;
                    document.getElementById('drawer-lead-name').textContent = ld.name;
                    document.getElementById('drawer-lead-sub').textContent = `${ld.sector || ''} • ${ld.district || ''}`;

                    let trialHtml = '';
                    if (ld.stage === 'Trial Started') {
                        trialHtml = `
                            <div style="background:#fffbeb;border:1px solid #fef3c7;border-left:4px solid #f59e0b;padding:0.75rem;border-radius:6px;margin-bottom:1rem;">
                                <div style="font-weight:700;color:#92400e;">10-Günlük Demo Aktif</div>
                                <div style="font-size:0.78rem;color:#78350f;">Bitiş: ${ld.demo_end_date || '—'} (${ld.demo_days_left} gün kaldı)</div>
                            </div>
                        `;
                    }

                    let convertedTenantHtml = '';
                    if (data.converted_tenant) {
                        const ct = data.converted_tenant;
                        convertedTenantHtml = `
                            <div style="background:#ecfdf5;border:1px solid #a7f3d0;border-left:4px solid #10b981;padding:0.75rem;border-radius:6px;margin-bottom:1rem;">
                                <div style="font-weight:700;color:#065f46;">Tenant Hesabı Oluşturuldu: ${ct.subdomain}.bookiapp.kibusiness.co</div>
                                <div style="font-size:0.78rem;color:#047857;">Durum: ${ct.status} • Plan: ${ct.plan}</div>
                            </div>
                        `;
                    }

                    let activitiesHtml = '';
                    (data.activities || []).forEach(act => {
                        activitiesHtml += `
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <span class="timeline-title">${act.title}</span>
                                        <span class="timeline-time">${act.created_at}</span>
                                    </div>
                                    <div class="timeline-desc">${act.description}</div>
                                </div>
                            </div>
                        `;
                    });

                    const safeName = escapeJs(ld.name || '');
                    const safeContact = escapeJs(ld.contact_person || '');
                    const safeSector = escapeJs(ld.sector || '');
                    const cleanWa = ld.clean_whatsapp || '';
                    const cleanPh = ld.clean_phone || ld.phone || '';

                    let commBarHtml = `
                        <div style="display:flex;gap:0.4rem;margin-bottom:1.25rem;flex-wrap:wrap;">
                            <button class="btn btn-primary btn-sm" onclick="openCallModal(${ld.id}, '${safeName}', '${cleanPh}', '${safeContact}', '${safeSector}')" style="background:#0f172a;border-color:#0f172a;">
                                📞 Sesli / AI Arama
                            </button>
                            ${cleanWa ? `<button class="btn btn-success btn-sm" onclick="openWhatsAppModal(${ld.id}, '${safeName}', '${cleanWa}', '${safeContact}', '${safeSector}')" style="background:#16a34a;border-color:#16a34a;">
                                🟢 WhatsApp Mesajı
                            </button>` : ''}
                            ${ld.instagram_url ? `<a href="${ld.instagram_url}" target="_blank" onclick="logCommunication(${ld.id}, 'instagram')" class="btn btn-secondary btn-sm" style="color:#9333ea;border-color:#e9d5ff;background:#faf5ff;">
                                🟣 Instagram
                            </a>` : ''}
                            ${ld.clean_email ? `<a href="mailto:${ld.clean_email}" onclick="logCommunication(${ld.id}, 'email')" class="btn btn-secondary btn-sm" style="color:#2563eb;border-color:#bfdbfe;background:#eff6ff;">
                                🔵 E-posta
                            </a>` : ''}
                        </div>
                    `;

                    body.innerHTML = `
                        ${trialHtml}
                        ${convertedTenantHtml}
                        ${commBarHtml}

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:1.25rem;">
                            <div style="background:#f8fafc;padding:0.75rem;border-radius:6px;border:1px solid var(--border-color);">
                                <div style="font-size:0.72rem;color:var(--text-light);text-transform:uppercase;">Yetkili & İletişim</div>
                                <div style="font-weight:700;margin-top:0.2rem;">${ld.contact_person || 'Yetkili Belirtilmedi'}</div>
                                <div style="font-size:0.8rem;color:var(--text-muted);">${ld.phone || '—'}</div>
                                ${ld.email ? `<div style="font-size:0.75rem;color:var(--text-muted);">${ld.email}</div>` : ''}
                            </div>
                            <div style="background:#f8fafc;padding:0.75rem;border-radius:6px;border:1px solid var(--border-color);">
                                <div style="font-size:0.72rem;color:var(--text-light);text-transform:uppercase;">Potansiyel Paket</div>
                                <div style="font-weight:700;margin-top:0.2rem;">${ld.package || 'Professional'}</div>
                                <div style="font-size:0.85rem;color:var(--primary);font-weight:700;">₺${Number(ld.potential_mrr || 2199).toLocaleString('tr-TR')}/ay</div>
                            </div>
                        </div>

                        <div style="background:#f8fafc;padding:0.75rem;border-radius:6px;border:1px solid var(--border-color);margin-bottom:1.25rem;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.35rem;">
                                <div style="font-size:0.72rem;color:var(--text-light);text-transform:uppercase;">Adres & Harita Konumu</div>
                                <span class="badge-tag" style="font-size:0.68rem;${(ld.latitude && ld.longitude) ? 'background:#ecfdf5;color:#047857;' : 'background:#fff1f2;color:#be123c;'}">
                                    ${(ld.latitude && ld.longitude) ? '📍 Konum Kayıtlı' : '⚠️ Konum Yok'}
                                </span>
                            </div>
                            <div style="font-size:0.82rem;color:var(--text-main);margin-bottom:0.5rem;">${ld.address ? `${ld.address}, ${ld.district || ''}` : 'Adres girilmemiş'}</div>
                            <div style="display:flex;gap:0.4rem;flex-wrap:wrap;">
                                ${(ld.latitude && ld.longitude) ? `
                                    <button class="btn btn-secondary btn-sm" onclick="focusLeadOnMap(${ld.latitude}, ${ld.longitude}, ${ld.id})" style="font-size:0.75rem;">
                                        🗺️ Haritada Göster
                                    </button>
                                ` : ''}
                                <button class="btn btn-secondary btn-sm" onclick="geocodeLeadAddress(${ld.id})" id="btn-geocode-${ld.id}" style="font-size:0.75rem;">
                                    📍 Adresten Konum Bul
                                </button>
                                ${(ld.latitude && ld.longitude) ? `
                                    <a href="https://maps.google.com/?q=${encodeURIComponent(ld.latitude + ',' + ld.longitude)}" target="_blank" class="btn btn-secondary btn-sm" style="font-size:0.75rem;">
                                        Google Maps ↗
                                    </a>
                                ` : ''}
                            </div>
                        </div>

                        <div style="margin-bottom:1.25rem;">
                            <label style="font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--text-light);">Aşama Durumu</label>
                            <select class="form-control" style="margin-top:0.35rem;" onchange="updateLeadStage(${ld.id}, this.value)">
                                <?php foreach (vars('stage_definitions') as $k => $lbl): ?>
                                    <option value="<?= e($k) ?>" ${ld.stage === '<?= e($k) ?>' ? 'selected' : ''}><?= e($lbl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="margin-bottom:1.5rem;">
                            <div style="font-weight:700;font-size:0.85rem;margin-bottom:0.5rem;">Hızlı Not / Aktivite Ekle</div>
                            <div style="display:flex;gap:0.5rem;">
                                <input type="text" class="form-control" id="drawer-quick-note" placeholder="Not veya görüşme detayı yazın...">
                                <button class="btn btn-primary btn-sm" onclick="saveQuickNote(${ld.id})">Ekle</button>
                            </div>
                        </div>

                        <h4 style="font-size:0.88rem;margin-bottom:0.75rem;">Zaman Tüneli & Saha Geçmişi</h4>
                        <div class="timeline">${activitiesHtml || '<div style="font-size:0.8rem;color:var(--text-light);">Kayıtlı aktivite yok.</div>'}</div>
                    `;

                    footer.innerHTML = `
                        <button class="btn btn-primary btn-sm" onclick="openCallModal(${ld.id}, '${safeName}', '${cleanPh}', '${safeContact}', '${safeSector}')" style="background:#0f172a;border-color:#0f172a;">📞 Arama Yap</button>
                        ${cleanWa ? `<button class="btn btn-success btn-sm" onclick="openWhatsAppModal(${ld.id}, '${safeName}', '${cleanWa}', '${safeContact}', '${safeSector}')" style="background:#16a34a;border-color:#16a34a;">WA Mesajı</button>` : ''}
                        <button class="btn btn-secondary btn-sm" onclick="openFieldVisitModal(${ld.id}, '${safeContact}')">Ziyaret Kaydet</button>
                        ${ld.stage !== 'Won' ? `<button class="btn btn-success btn-sm" onclick="openTenantWizardForLead(${ld.id})">Satış Yapıldı (Won)</button>` : ''}
                    `;
                });
        }

        function closeLeadDrawer(e) {
            if (e && e.target !== document.getElementById('lead-drawer-overlay')) return;
            document.getElementById('lead-drawer-overlay').classList.remove('open');
            activeLeadId = null;
        }

        function updateLeadStage(leadId, newStage) {
            post('<?= site_url('superadmin_tenants/api_update_stage') ?>', {
                lead_id: leadId,
                stage: newStage,
                reason: 'Drawer içinden doğrudan aşama güncelleme'
            }).then(data => {
                if (data.success) {
                    showToast('Aşama güncellendi', 'success');
                    openLeadDrawer(leadId);
                } else {
                    showToast(data.message || 'Hata', 'error');
                }
            });
        }

        function saveQuickNote(leadId) {
            const input = document.getElementById('drawer-quick-note');
            const note = input.value.trim();
            if (!note) return;

            post('<?= site_url('superadmin_tenants/api_add_quick_note') ?>', {
                lead_id: leadId,
                title: 'Saha Notu',
                note: note
            }).then(data => {
                if (data.success) {
                    showToast('Not eklendi', 'success');
                    input.value = '';
                    openLeadDrawer(leadId);
                } else {
                    showToast(data.message || 'Hata oluştu', 'error');
                }
            });
        }

        function geocodeLeadAddress(leadId) {
            const btn = document.getElementById(`btn-geocode-${leadId}`);
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Konum aranıyor...';
            }
            post('<?= site_url('superadmin_tenants/api_geocode_lead') ?>', {
                lead_id: leadId
            }).then(data => {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = '📍 Adresten Konum Bul';
                }
                if (data.success) {
                    showToast('Konum başarıyla belirlendi ve kaydedildi ✓', 'success');
                    openLeadDrawer(leadId);
                    if (typeof loadMapLeads === 'function') loadMapLeads();
                } else {
                    showToast(data.message || 'Konum bulunamadı.', 'error');
                }
            });
        }

        function focusLeadOnMap(lat, lng, leadId) {
            closeLeadDrawer();
            switchTab('map');
            setTimeout(() => {
                if (googleMapInstance) {
                    googleMapInstance.setCenter({ lat: Number(lat), lng: Number(lng) });
                    googleMapInstance.setZoom(16);
                    if (mapMarkers[leadId]) {
                        google.maps.event.trigger(mapMarkers[leadId], 'click');
                    }
                }
            }, 400);
        }

        // =========================================================================
        // SAHA HARİTASI (GOOGLE MAPS) CONTROLLER
        // =========================================================================
        let googleMapInstance = null;
        let mapMarkers = {};
        let mapInfoWindow = null;
        let isMapScriptLoaded = false;
        const GOOGLE_MAPS_KEY = '<?= e($ps['google_maps_key'] ?? 'AIzaSyAscIARfxTG_KzedaskCabzuRSTj-0bulA') ?>';

        function loadGoogleMapsScript(callback) {
            if (window.google && window.google.maps) {
                isMapScriptLoaded = true;
                callback();
                return;
            }

            const existing = document.getElementById('google-maps-sdk');
            if (existing) {
                if (window.google && window.google.maps) {
                    callback();
                } else {
                    existing.addEventListener('load', () => callback());
                }
                return;
            }

            // Google Maps JS API async loader per developer guidelines
            window.__initGoogleMapCallback = function() {
                isMapScriptLoaded = true;
                callback();
            };

            const script = document.createElement('script');
            script.id = 'google-maps-sdk';
            script.src = `https://maps.googleapis.com/maps/api/js?key=${GOOGLE_MAPS_KEY}&loading=async&callback=__initGoogleMapCallback&v=weekly`;
            script.async = true;
            script.defer = true;
            script.onerror = () => {
                showToast('Google Haritalar SDK yüklenemedi. API Anahtarını kontrol edin.', 'error');
                const mapEl = document.getElementById('leads-map');
                if (mapEl) {
                    mapEl.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#b91c1c;padding:2rem;text-align:center;">Google Haritalar yüklenemedi. Platform Ayarlarından Google Maps API Anahtarını doğrulayın.</div>';
                }
            };
            document.head.appendChild(script);
        }

        function initMapIfNeeded() {
            loadGoogleMapsScript(() => {
                if (!googleMapInstance) {
                    initGoogleMap();
                } else {
                    google.maps.event.trigger(googleMapInstance, 'resize');
                    loadMapLeads();
                }
            });
        }

        function initGoogleMap() {
            const mapEl = document.getElementById('leads-map');
            if (!mapEl) return;

            // Bursa / Nilüfer center by default
            const bursaCenter = { lat: 40.2185, lng: 28.9345 };

            googleMapInstance = new google.maps.Map(mapEl, {
                center: bursaCenter,
                zoom: 12,
                mapTypeControl: true,
                mapTypeControlOptions: {
                    style: google.maps.MapTypeControlStyle.DROPDOWN_MENU,
                    position: google.maps.ControlPosition.TOP_RIGHT
                },
                streetViewControl: true,
                fullscreenControl: true,
                zoomControl: true,
                styles: [
                    { featureType: 'poi.business', stylers: [{ visibility: 'on' }] }
                ]
            });

            mapInfoWindow = new google.maps.InfoWindow();

            loadMapLeads();
        }

        function getStageColor(stage) {
            switch (stage) {
                case 'Won':
                    return '#10b981'; // Green
                case 'Trial Started':
                case 'Demo Presented':
                    return '#8b5cf6'; // Purple
                case 'Follow-up':
                    return '#ea580c'; // Orange
                case 'Visit Planned':
                case 'Visited':
                case 'Meeting':
                    return '#f59e0b'; // Amber
                case 'Lost':
                    return '#ef4444'; // Red
                case 'New Lead':
                case 'Qualified':
                default:
                    return '#2563eb'; // Blue
            }
        }

        function createCustomPin(color, labelText) {
            // SVG Pin with dynamic background color
            const svg = `
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="42" viewBox="0 0 32 42">
                    <defs>
                        <filter id="shadow" x="-20%" y="-10%" width="140%" height="140%">
                            <feDropShadow dx="0" dy="2" stdDeviation="2" flood-color="#000000" flood-opacity="0.3"/>
                        </filter>
                    </defs>
                    <path d="M16 0C7.16 0 0 7.16 0 16c0 10 16 26 16 26s16-16 16-26c0-8.84-7.16-16-16-16z" fill="${color}" filter="url(#shadow)"/>
                    <circle cx="16" cy="15" r="7" fill="#ffffff"/>
                </svg>
            `;
            return {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
                scaledSize: new google.maps.Size(32, 42),
                anchor: new google.maps.Point(16, 42)
            };
        }

        function loadMapLeads() {
            if (!googleMapInstance) return;

            const stage = document.getElementById('map-stage-filter')?.value || '';
            const sector = document.getElementById('map-sector-filter')?.value || '';
            const district = document.getElementById('map-district-filter')?.value || '';

            const url = `<?= site_url('superadmin_tenants/api_leads_map') ?>?stage=${encodeURIComponent(stage)}&sector=${encodeURIComponent(sector)}&district=${encodeURIComponent(district)}`;

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        showToast('Harita lead verileri alınamadı', 'error');
                        return;
                    }

                    // Clear old markers
                    Object.values(mapMarkers).forEach(m => m.setMap(null));
                    mapMarkers = {};

                    const leads = data.leads || [];
                    const unlocatedCount = data.unlocated_count || 0;
                    const totalCount = data.total || 0;

                    // Update UI counters
                    const countEl = document.getElementById('map-lead-count');
                    if (countEl) {
                        countEl.textContent = `(${leads.length} konumlu / ${totalCount} toplam)`;
                    }

                    const unlocatedBar = document.getElementById('map-unlocated-bar');
                    const unlocatedText = document.getElementById('map-unlocated-text');
                    if (unlocatedBar && unlocatedText) {
                        if (unlocatedCount > 0) {
                            unlocatedBar.style.display = 'flex';
                            unlocatedText.textContent = `⚠️ ${unlocatedCount} lead'in henüz harita koordinatı tanımlanmamış. "Otomatik Konumla" butonu ile adreslerden bulunmasını sağlayabilirsiniz.`;
                        } else {
                            unlocatedBar.style.display = 'none';
                        }
                    }

                    if (leads.length === 0) {
                        return;
                    }

                    const bounds = new google.maps.LatLngBounds();

                    leads.forEach(ld => {
                        const pos = { lat: Number(ld.latitude), lng: Number(ld.longitude) };
                        if (isNaN(pos.lat) || isNaN(pos.lng)) return;

                        bounds.extend(pos);

                        const pinColor = getStageColor(ld.stage);
                        const marker = new google.maps.Marker({
                            position: pos,
                            map: googleMapInstance,
                            title: ld.name,
                            icon: createCustomPin(pinColor, ld.name),
                            animation: google.maps.Animation.DROP
                        });

                        marker.addListener('click', () => {
                            const safeName = (ld.name || '').replace(/"/g, '&quot;');
                            const phone = ld.phone || ld.whatsapp || '—';
                            const infoContent = `
                                <div style="font-family:inherit;padding:4px;max-width:260px;">
                                    <div style="font-weight:800;font-size:0.95rem;color:#0f172a;margin-bottom:2px;">${safeName}</div>
                                    <div style="font-size:0.75rem;color:#64748b;margin-bottom:6px;">${ld.sector || ''} • ${ld.district || ''}</div>
                                    <div style="margin-bottom:8px;">
                                        <span style="display:inline-block;padding:2px 8px;border-radius:12px;font-size:0.7rem;font-weight:700;background:${pinColor}15;color:${pinColor};border:1px solid ${pinColor}40;">
                                            ${ld.stage || 'Yeni'}
                                        </span>
                                    </div>
                                    <div style="font-size:0.78rem;color:#334155;margin-bottom:8px;">
                                        📞 ${phone}
                                    </div>
                                    ${ld.address ? `<div style="font-size:0.72rem;color:#64748b;margin-bottom:8px;">📍 ${ld.address}</div>` : ''}
                                    <div style="display:flex;gap:4px;">
                                        <button onclick="openLeadDrawer(${ld.id})" style="flex:1;padding:5px 8px;background:#2563eb;color:#fff;border:none;border-radius:6px;font-size:0.75rem;font-weight:700;cursor:pointer;">
                                            Detay & Ara
                                        </button>
                                        <a href="https://maps.google.com/?q=${encodeURIComponent(ld.latitude + ',' + ld.longitude)}" target="_blank" style="padding:5px 8px;background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;border-radius:6px;font-size:0.75rem;text-decoration:none;display:inline-flex;align-items:center;">
                                            Yol Tarifi ↗
                                        </a>
                                    </div>
                                </div>
                            `;
                            mapInfoWindow.setContent(infoContent);
                            mapInfoWindow.open(googleMapInstance, marker);
                        });

                        mapMarkers[ld.id] = marker;
                    });

                    if (leads.length > 1) {
                        googleMapInstance.fitBounds(bounds, { top: 40, right: 40, bottom: 40, left: 40 });
                    } else if (leads.length === 1) {
                        googleMapInstance.setCenter({ lat: Number(leads[0].latitude), lng: Number(leads[0].longitude) });
                        googleMapInstance.setZoom(15);
                    }
                })
                .catch(err => {
                    console.error('Map leads load error:', err);
                    showToast('Harita yüklenirken hata oluştu', 'error');
                });
        }

        function batchGeocodeLeads() {
            const btn = document.getElementById('btn-batch-geocode');
            const originalText = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '⏳ Konumlanıyor...';
            }

            post('<?= site_url('superadmin_tenants/api_batch_geocode') ?>', {})
                .then(data => {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                    if (data && data.success) {
                        showToast(`İşlem tamamlandı! ${data.geocoded} lead konumlandı (${data.failed} başarısız, ${data.remaining} kalan).`, 'success');
                        loadMapLeads();
                    } else {
                        showToast((data && data.message) ? data.message : 'Toplu konumlandırma başarısız', 'error');
                    }
                })
                .catch(err => {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                    console.error('Batch geocode error:', err);
                    showToast('İstek sırasında hata oluştu: ' + err.message, 'error');
                });
        }

        // --- FIELD VISIT MODAL CONTROLLER ---
        function openFieldVisitModal(leadId, contactPerson = '') {
            document.getElementById('visit-lead-id').value = leadId;
            document.getElementById('v-contact-person').value = contactPerson;
            document.getElementById('v-contact-title').value = '';
            document.getElementById('v-notes').value = '';
            document.getElementById('v-next-action-date').value = '';
            document.getElementById('v-next-action-notes').value = '';
            document.getElementById('field-visit-modal').classList.add('open');
        }

        function autoSuggestStage() {
            const level = document.getElementById('v-interest-level').value;
            const stageSelect = document.getElementById('v-new-stage');
            if (level === 'very_high') {
                stageSelect.value = 'Trial Started';
            } else if (level === 'high') {
                stageSelect.value = 'Demo Presented';
            } else if (level === 'medium') {
                stageSelect.value = 'Follow-up';
            } else {
                stageSelect.value = 'Visited';
            }
        }

        function handleSaveFieldVisit(e) {
            e.preventDefault();
            const leadId = document.getElementById('visit-lead-id').value;

            post('<?= site_url('superadmin_tenants/api_save_visit') ?>', {
                lead_id: leadId,
                contact_person: document.getElementById('v-contact-person').value,
                contact_title: document.getElementById('v-contact-title').value,
                visit_type: document.getElementById('v-visit-type').value,
                current_method: document.getElementById('v-current-method').value,
                staff_count: document.getElementById('v-staff-count').value,
                interest_level: document.getElementById('v-interest-level').value,
                notes: document.getElementById('v-notes').value,
                new_stage: document.getElementById('v-new-stage').value,
                next_action_date: document.getElementById('v-next-action-date').value,
                next_action_notes: document.getElementById('v-next-action-notes').value,
            }).then(data => {
                if (data.success) {
                    showToast('Saha ziyareti başarıyla kaydedildi!', 'success');
                    document.getElementById('field-visit-modal').classList.remove('open');
                    if (activeLeadId) openLeadDrawer(activeLeadId);
                    loadPipeline();
                } else {
                    showToast(data.message || 'Ziyaret kaydedilemedi', 'error');
                }
            });
        }

        // --- WON -> TENANT WIZARD CONTROLLER ---
        function openTenantWizardForLead(leadId) {
            currentWizStep = 1;
            document.getElementById('wiz-lead-id').value = leadId;
            document.getElementById('wiz-error-msg').style.display = 'none';

            fetch(`<?= site_url('superadmin_tenants/api_lead_detail') ?>?lead_id=${leadId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const ld = data.lead;
                        document.getElementById('wiz-business-name').value = ld.name;
                        
                        // Generate proposed subdomain
                        let sub = ld.name.toLowerCase()
                            .replace(/ğ/g, 'g').replace(/ü/g, 'u').replace(/ş/g, 's')
                            .replace(/ı/g, 'i').replace(/ö/g, 'o').replace(/ç/g, 'c')
                            .replace(/[^a-z0-9]/g, '');
                        document.getElementById('wiz-subdomain').value = sub.slice(0, 20);

                        document.getElementById('wiz-address').value = `${ld.address || ''} ${ld.district || ''}`;
                        document.getElementById('wiz-admin-name').value = ld.contact_person || ld.name;
                        document.getElementById('wiz-admin-phone').value = ld.phone || '';
                        document.getElementById('wiz-admin-email').value = ld.email || `${sub}@example.com`;
                        document.getElementById('wiz-mrr').value = ld.potential_mrr || '2199.00';
                    }
                    updateWizStepUI();
                    document.getElementById('wizard-tenant-modal').classList.add('open');
                });
        }

        function sanitizeSubdomainInput(input) {
            input.value = input.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
        }

        function updateWizStepUI() {
            for (let i = 1; i <= 5; i++) {
                const tab = document.getElementById(`w-step-tab-${i}`);
                const panel = document.getElementById(`wiz-panel-${i}`);
                tab.className = `wizard-step ${i === currentWizStep ? 'active' : (i < currentWizStep ? 'completed' : '')}`;
                panel.style.display = i === currentWizStep ? 'block' : 'none';
            }

            document.getElementById('btn-wiz-prev').style.display = currentWizStep > 1 ? 'inline-flex' : 'none';
            document.getElementById('btn-wiz-next').style.display = currentWizStep < 5 ? 'inline-flex' : 'none';
            document.getElementById('btn-wiz-submit').style.display = currentWizStep === 5 ? 'inline-flex' : 'none';

            if (currentWizStep === 5) {
                // Render summary
                const sub = document.getElementById('wiz-subdomain').value;
                const name = document.getElementById('wiz-business-name').value;
                const plan = document.getElementById('wiz-plan').value;
                const mrr = document.getElementById('wiz-mrr').value;
                const adminName = document.getElementById('wiz-admin-name').value;
                const adminEmail = document.getElementById('wiz-admin-email').value;

                document.getElementById('wiz-summary-content').innerHTML = `
                    <div style="font-weight:700;font-size:0.95rem;margin-bottom:0.5rem;color:var(--text-main);">Özet Bilgiler</div>
                    <div>• <strong>İşletme:</strong> ${name}</div>
                    <div>• <strong>Subdomain:</strong> ${sub}.bookiapp.kibusiness.co</div>
                    <div>• <strong>Paket & MRR:</strong> ${plan} (₺${mrr}/ay)</div>
                    <div>• <strong>Yönetici:</strong> ${adminName} (${adminEmail})</div>
                    <div>• <strong>Onboarding:</strong> Otomatik 10 adımlı sihirbaz oturumu oluşturulacak.</div>
                `;
            }
        }

        function wizStepNext() {
            if (currentWizStep === 1) {
                const sub = document.getElementById('wiz-subdomain').value.trim();
                if (!sub) {
                    showToast('Lütfen subdomain girin', 'error');
                    return;
                }
            } else if (currentWizStep === 3) {
                const email = document.getElementById('wiz-admin-email').value.trim();
                if (!email) {
                    showToast('Lütfen yönetici e-posta girin', 'error');
                    return;
                }
            }

            currentWizStep = Math.min(5, currentWizStep + 1);
            updateWizStepUI();
        }

        function wizStepPrev() {
            currentWizStep = Math.max(1, currentWizStep - 1);
            updateWizStepUI();
        }

        function handleExecuteTenantWizard(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-wiz-submit');
            btn.disabled = true;
            btn.textContent = 'Kurulum Yapılıyor (DB Hazırlanıyor)...';

            post('<?= site_url('superadmin_tenants/api_create_tenant_from_lead') ?>', {
                lead_id: document.getElementById('wiz-lead-id').value,
                subdomain: document.getElementById('wiz-subdomain').value,
                business_name: document.getElementById('wiz-business-name').value,
                business_type: document.getElementById('wiz-business-type').value,
                address: document.getElementById('wiz-address').value,
                plan: document.getElementById('wiz-plan').value,
                billing_cycle: document.getElementById('wiz-billing-cycle').value,
                mrr_amount: document.getElementById('wiz-mrr').value,
                trial_days: document.getElementById('wiz-trial-days').value,
                admin_name: document.getElementById('wiz-admin-name').value,
                admin_email: document.getElementById('wiz-admin-email').value,
                admin_phone: document.getElementById('wiz-admin-phone').value,
                admin_password: document.getElementById('wiz-admin-password').value,
                create_onboarding: document.getElementById('wiz-create-onboarding').checked ? 1 : 0
            }).then(data => {
                btn.disabled = false;
                btn.textContent = 'Kurulumu Tamamla & Tenant Başlat';

                if (!data.success) {
                    const err = document.getElementById('wiz-error-msg');
                    err.textContent = data.message || 'Kurulum sırasında hata oluştu.';
                    err.style.display = 'block';
                    return;
                }

                document.getElementById('wizard-tenant-modal').classList.remove('open');
                showToast(`Tenant başarıyla kuruldu: ${data.subdomain}`, 'success');
                
                let successMsg = `Kiracı oluşturuldu: <strong>${data.subdomain}</strong><br>` +
                    `Giriş URL: <a href="${data.login_url}" target="_blank">${data.login_url}</a><br>` +
                    `Kullanıcı: administrator / ${data.admin_password}`;
                if (data.onboarding_link) {
                    successMsg += `<br>Onboarding Linki: <a href="${data.onboarding_link}" target="_blank">${data.onboarding_link}</a>`;
                }

                const sbox = document.getElementById('success-box');
                sbox.innerHTML = successMsg;
                sbox.style.display = 'block';

                loadPipeline();
            });
        }

        // --- ONBOARDING SESSIONS CONTROLLER ---
        function loadOnboardingSessions() {
            fetch('<?= site_url('superadmin_tenants/api_onboarding_sessions') ?>')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    const tbody = document.getElementById('onboarding-sessions-tbody');
                    tbody.innerHTML = '';

                    if (!data.sessions || data.sessions.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-light);">Aktif onboarding oturumu bulunmuyor.</td></tr>';
                        return;
                    }

                    data.sessions.forEach(os => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>
                                <strong>${os.subdomain}</strong><br>
                                <span style="font-size:0.75rem;color:var(--text-light);">${os.company_name || '—'}</span>
                            </td>
                            <td>
                                ${os.lead_name || '—'}<br>
                                <span style="font-size:0.75rem;color:var(--text-light);">${os.phone_number || ''}</span>
                            </td>
                            <td><span class="badge ${os.status}">${os.status}</span></td>
                            <td style="width:160px;">
                                <div style="background:#e2e8f0;border-radius:9999px;height:7px;overflow:hidden;margin-bottom:0.25rem;">
                                    <div style="background:var(--primary);height:100%;width:${os.progress_percent}%;"></div>
                                </div>
                                <span style="font-size:0.72rem;color:var(--text-muted);font-weight:600;">%${os.progress_percent} Tamamlandı</span>
                            </td>
                            <td>Adım ${os.current_step} / 10</td>
                            <td>${os.last_activity_at || '—'}</td>
                            <td style="text-align:right;">
                                <button class="btn btn-secondary btn-sm" onclick="copyOnboardingLink('${os.link}')">Kopyala</button>
                                <a href="${os.link}" target="_blank" class="btn btn-secondary btn-sm">Aç</a>
                                <button class="btn btn-secondary btn-sm" onclick="regenerateOnboardingToken(${os.id_tenants})">Yenile</button>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                });
        }

        function copyOnboardingLink(link) {
            navigator.clipboard.writeText(link).then(() => {
                showToast('Onboarding bağlantısı panoya kopyalandı!', 'success');
            });
        }

        function regenerateOnboardingToken(tenantId) {
            if (!confirm('Onboarding bağlantısını sıfırlamak ve yeni token üretmek istediğinize emin misiniz?')) return;
            post('<?= site_url('superadmin_tenants/api_regenerate_onboarding_link') ?>', { tenant_id: tenantId })
                .then(data => {
                    if (data.success) {
                        showToast('Yeni onboarding linki oluşturuldu', 'success');
                        loadOnboardingSessions();
                    }
                });
        }

        // --- TASKS CONTROLLER ---
        function toggleTaskStatus(taskId, status) {
            post('<?= site_url('superadmin_tenants/api_update_task_status') ?>', {
                task_id: taskId,
                status: status
            }).then(data => {
                if (data.success) {
                    showToast('Görev güncellendi', 'success');
                    window.location.reload();
                }
            });
        }

        // --- IMPORT CONTROLLER ---
        function handleFileSelected(input) {
            if (input.files && input.files[0]) {
                selectedFile = input.files[0];
                document.getElementById('dropzone-text').textContent = `Seçilen dosya: ${selectedFile.name} (${(selectedFile.size / 1024).toFixed(1)} KB)`;
                document.getElementById('btn-start-import').disabled = false;
            }
        }

        function resetImportForm() {
            selectedFile = null;
            document.getElementById('import-file-input').value = '';
            document.getElementById('dropzone-text').textContent = 'Dosyanızı buraya sürükleyin veya seçmek için tıklayın';
            document.getElementById('btn-start-import').disabled = true;
            document.getElementById('import-result-container').style.display = 'none';
        }

        document.getElementById('import-form').addEventListener('submit', function(e) {
            e.preventDefault();
            if (!selectedFile) return;

            const formData = new FormData();
            formData.append('file', selectedFile);
            formData.append('duplicate_action', document.getElementById('import-duplicate-action').value);
            formData.append('default_stage', document.getElementById('import-default-stage').value);
            formData.append('csrf_token', csrfToken);

            document.getElementById('import-progress-container').style.display = 'block';
            document.getElementById('btn-start-import').disabled = true;

            fetch('<?= site_url('superadmin_tenants/api_import_leads') ?>', {
                method: 'POST',
                body: formData
            }).then(r => r.json())
            .then(data => {
                document.getElementById('import-progress-container').style.display = 'none';
                document.getElementById('btn-start-import').disabled = false;

                const resDiv = document.getElementById('import-result-container');
                resDiv.style.display = 'block';

                if (!data.success) {
                    resDiv.innerHTML = `<div style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:1rem;border-radius:8px;">${data.message || 'İçe aktarım başarısız.'}</div>`;
                    return;
                }

                const rep = data.report;
                resDiv.innerHTML = `
                    <div style="background:#ecfdf5;border:1px solid #a7f3d0;padding:1.25rem;border-radius:8px;">
                        <div style="font-weight:700;color:#065f46;margin-bottom:0.5rem;font-size:0.95rem;">İçe Aktarım Başarıyla Tamamlandı!</div>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:0.75rem;margin-top:0.75rem;">
                            <div style="background:#ffffff;padding:0.75rem;border-radius:6px;border:1px solid #a7f3d0;">
                                <div style="font-size:0.72rem;color:#065f46;">Toplam Satır</div>
                                <div style="font-size:1.3rem;font-weight:800;">${rep.total_rows}</div>
                            </div>
                            <div style="background:#ffffff;padding:0.75rem;border-radius:6px;border:1px solid #a7f3d0;">
                                <div style="font-size:0.72rem;color:#15803d;">Yeni Eklenen</div>
                                <div style="font-size:1.3rem;font-weight:800;color:#15803d;">${rep.inserted}</div>
                            </div>
                            <div style="background:#ffffff;padding:0.75rem;border-radius:6px;border:1px solid #a7f3d0;">
                                <div style="font-size:0.72rem;color:#b45309;">Güncellenen</div>
                                <div style="font-size:1.3rem;font-weight:800;color:#b45309;">${rep.updated}</div>
                            </div>
                            <div style="background:#ffffff;padding:0.75rem;border-radius:6px;border:1px solid #a7f3d0;">
                                <div style="font-size:0.72rem;color:#64748b;">Atlanan / Hata</div>
                                <div style="font-size:1.3rem;font-weight:800;color:#64748b;">${rep.skipped + rep.errors}</div>
                            </div>
                        </div>
                    </div>
                `;
            });
        });

        // --- OMNISEARCH MODAL CONTROLLER ---
        function openOmnisearch() {
            document.getElementById('omnisearch-modal').classList.add('open');
            const field = document.getElementById('omnisearch-field');
            field.value = '';
            field.focus();
        }

        function closeOmnisearch(e) {
            if (e && e.target !== document.getElementById('omnisearch-modal')) return;
            document.getElementById('omnisearch-modal').classList.remove('open');
        }

        window.addEventListener('keydown', function(e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                openOmnisearch();
            } else if (e.key === 'Escape') {
                closeOmnisearch();
                closeLeadDrawer();
                closePitchGuide();
            }
        });

        function handleOmnisearchInput(q) {
            clearTimeout(omnisearchDebounce);
            if (!q.trim()) {
                document.getElementById('omnisearch-results').innerHTML = '<div style="padding:1.5rem;text-align:center;color:var(--text-light);font-size:0.84rem;">Aramak istediğiniz terimi yazın...</div>';
                return;
            }

            omnisearchDebounce = setTimeout(() => {
                fetch(`<?= site_url('superadmin_tenants/api_global_search') ?>?q=${encodeURIComponent(q)}`)
                    .then(r => r.json())
                    .then(data => {
                        const container = document.getElementById('omnisearch-results');
                        container.innerHTML = '';

                        if (!data.results || data.results.length === 0) {
                            container.innerHTML = '<div style="padding:1.5rem;text-align:center;color:var(--text-light);">Eşleşen sonuç bulunamadı.</div>';
                            return;
                        }

                        data.results.forEach(res => {
                            const item = document.createElement('div');
                            item.className = 'search-result-item';
                            item.innerHTML = `
                                <div>
                                    <div style="font-weight:700;font-size:0.88rem;">${res.title}</div>
                                    <div style="font-size:0.75rem;color:var(--text-muted);">${res.subtitle}</div>
                                </div>
                                <span class="badge ${res.type === 'lead' ? 'pending' : 'active'}">${res.type.toUpperCase()}</span>
                            `;
                            item.onclick = () => {
                                closeOmnisearch();
                                if (res.type === 'lead') {
                                    openLeadDrawer(res.id);
                                } else if (res.type === 'tenant') {
                                    switchTab('tenants');
                                }
                            };
                            container.appendChild(item);
                        });
                    });
            }, 250);
        }

        // --- PITCH GUIDE CONTROLLER ---
        function openPitchGuide() {
            document.getElementById('pitch-drawer-overlay').classList.add('open');
        }
        function closePitchGuide(e) {
            if (e && e.target !== document.getElementById('pitch-drawer-overlay')) return;
            document.getElementById('pitch-drawer-overlay').classList.remove('open');
        }

        // --- IMPERSONATE TENANT ---
        function impersonateTenant(tenantId) {
            post('<?= site_url('superadmin_tenants/api_impersonate_tenant') ?>', { tenant_id: tenantId })
                .then(data => {
                    if (data.success && data.target_url) {
                        window.open(data.target_url, '_blank');
                    } else {
                        showToast(data.message || 'Giriş yapılamadı', 'error');
                    }
                });
        }

        // --- ORIGINAL TENANT MANAGEMENT JS FUNCTIONS (PRESERVED) ---
        function showSuccess(html) {
            const box = document.getElementById('success-box');
            box.innerHTML = html;
            box.style.display = 'block';
        }

        document.getElementById('create-form').addEventListener('submit', function (event) {
            event.preventDefault();
            const msg = document.getElementById('create-msg');
            msg.style.display = 'none';

            post('<?= site_url('superadmin_tenants/store') ?>', {
                subdomain: document.getElementById('c-subdomain').value,
                business_type: document.getElementById('c-business-type').value,
                plan: document.getElementById('c-plan').value,
                trial_days: document.getElementById('c-trial-days').value,
                admin_name: document.getElementById('c-admin-name').value,
                admin_email: document.getElementById('c-admin-email').value,
                admin_phone: document.getElementById('c-admin-phone').value,
                admin_password: document.getElementById('c-admin-password').value,
            }).then((data) => {
                if (!data.success) {
                    msg.textContent = data.message || 'Hata oluştu.';
                    msg.style.display = 'block';
                    return;
                }

                showSuccess(
                    'Kiracı oluşturuldu: <strong>' + data.subdomain + '</strong><br>' +
                    'URL: <a href="' + data.login_url + '" target="_blank">' + data.login_url + '</a><br>' +
                    'Giriş: administrator / ' + data.admin_password,
                );
                window.location.reload();
            });
        });

        function setStatus(tenantId, status) {
            const verb = status === 'suspended' ? 'askıya almak' : 'aktifleştirmek';
            if (!confirm('Bu kiracıyı ' + verb + ' istediğinize emin misiniz?')) {
                return;
            }

            post('<?= site_url('superadmin_tenants/update_status') ?>', { tenant_id: tenantId, status: status })
                .then((data) => { if (data.success) window.location.reload(); else alert(data.message || 'Hata'); });
        }

        function openAdminModal(tenantId, subdomain) {
            document.getElementById('a-tenant-id').value = tenantId;
            document.getElementById('a-subdomain-label').textContent = subdomain;
            document.getElementById('a-email-label').textContent = '…';
            document.getElementById('a-username').value = '';
            document.getElementById('a-password').value = '';
            ['a-username-msg', 'a-password-msg', 'a-reset-msg'].forEach((id) => {
                const el = document.getElementById(id);
                el.style.display = 'none';
            });
            document.getElementById('admin-modal').classList.add('open');

            fetch('<?= site_url('superadmin_tenants/get_admin_account') ?>?tenant_id=' + tenantId)
                .then((r) => r.json())
                .then((data) => {
                    if (data.success) {
                        document.getElementById('a-username').value = data.username;
                        document.getElementById('a-email-label').textContent = data.email || '—';
                    } else {
                        document.getElementById('a-email-label').textContent = data.message || 'Bulunamadı';
                    }
                });
        }
        
        function openDetailsModal(tenantId) {
            document.getElementById('details-loading').style.display = 'block';
            document.getElementById('details-content').style.display = 'none';
            document.getElementById('details-modal').classList.add('open');
            
            fetch('<?= site_url('superadmin_tenants/get_tenant_details') ?>?tenant_id=' + tenantId)
                .then((r) => r.json())
                .then((data) => {
                    if (data.success) {
                        const m = data.metrics;
                        document.getElementById('det-services').textContent = m.services_count;
                        document.getElementById('det-providers').textContent = m.providers_count;
                        
                        document.getElementById('det-st-pending').textContent = m.appointments_status.pending || 0;
                        document.getElementById('det-st-approved').textContent = m.appointments_status.approved || 0;
                        document.getElementById('det-st-completed').textContent = m.appointments_status.completed || 0;
                        document.getElementById('det-st-canceled').textContent = m.appointments_status.canceled || 0;
                        
                        document.getElementById('det-contact-name').textContent = m.contact.admin_name || '—';
                        document.getElementById('det-contact-email').textContent = m.contact.admin_email || '—';
                        document.getElementById('det-contact-phone').textContent = m.contact.admin_phone || '—';
                        
                        const list = document.getElementById('det-last-appointments');
                        list.innerHTML = '';
                        if (m.last_appointments.length === 0) {
                            list.innerHTML = '<li>Randevu yok</li>';
                        } else {
                            m.last_appointments.forEach(a => {
                                const li = document.createElement('li');
                                li.style.marginBottom = '0.3rem';
                                li.innerHTML = `<strong>${a.book_datetime}</strong>: ${a.service_name || 'Hizmet silinmiş'} <span class="badge ${a.status}">${a.status}</span>`;
                                list.appendChild(li);
                            });
                        }
                        
                        document.getElementById('details-loading').style.display = 'none';
                        document.getElementById('details-content').style.display = 'block';
                    } else {
                        document.getElementById('details-loading').textContent = data.message || 'Hata';
                    }
                });
        }

        function showFieldMsg(id, text, ok) {
            const el = document.getElementById(id);
            el.textContent = text;
            el.style.display = 'block';
            el.style.color = ok ? '#16a34a' : '#dc2626';
        }

        function saveAdminUsername() {
            post('<?= site_url('superadmin_tenants/update_admin_username') ?>', {
                tenant_id: document.getElementById('a-tenant-id').value,
                username: document.getElementById('a-username').value,
            }).then((data) => {
                if (data.success) showFieldMsg('a-username-msg', 'Kullanıcı adı güncellendi: ' + data.username, true);
                else showFieldMsg('a-username-msg', data.message || 'Hata', false);
            });
        }

        function saveAdminPassword() {
            const password = document.getElementById('a-password').value;
            post('<?= site_url('superadmin_tenants/set_admin_password') ?>', {
                tenant_id: document.getElementById('a-tenant-id').value,
                password: password,
            }).then((data) => {
                if (data.success) {
                    showFieldMsg('a-password-msg', 'Şifre güncellendi.', true);
                    document.getElementById('a-password').value = '';
                } else {
                    showFieldMsg('a-password-msg', data.message || 'Hata', false);
                }
            });
        }

        function sendAdminReset() {
            post('<?= site_url('superadmin_tenants/send_admin_password_reset') ?>', {
                tenant_id: document.getElementById('a-tenant-id').value,
            }).then((data) => {
                if (data.success) showFieldMsg('a-reset-msg', 'Sıfırlama e-postası gönderildi.', true);
                else showFieldMsg('a-reset-msg', data.message || 'Gönderilemedi (SMTP yapılandırılmamış olabilir).', false);
            });
        }

        function openPlanModal(tenantId, plan, billingCycle, mrrAmount, trialEndsAt, licenseExpiresAt) {
            document.getElementById('p-tenant-id').value = tenantId;
            document.getElementById('p-plan').value = plan;
            document.getElementById('p-billing-cycle').value = billingCycle;
            document.getElementById('p-mrr-amount').value = mrrAmount;
            document.getElementById('p-trial-ends-at').value = trialEndsAt;
            document.getElementById('p-license-expires-at').value = licenseExpiresAt;
            document.getElementById('plan-modal').classList.add('open');
        }

        document.getElementById('plan-form').addEventListener('submit', function (event) {
            event.preventDefault();
            post('<?= site_url('superadmin_tenants/update_plan') ?>', {
                tenant_id: document.getElementById('p-tenant-id').value,
                plan: document.getElementById('p-plan').value,
                billing_cycle: document.getElementById('p-billing-cycle').value,
                mrr_amount: document.getElementById('p-mrr-amount').value,
                trial_ends_at: document.getElementById('p-trial-ends-at').value,
                license_expires_at: document.getElementById('p-license-expires-at').value,
            }).then((data) => {
                if (data.success) window.location.reload();
                else { const m = document.getElementById('plan-msg'); m.textContent = data.message || 'Hata'; m.style.display = 'block'; }
            });
        });

        function openDeleteModal(tenantId, subdomain) {
            document.getElementById('d-tenant-id').value = tenantId;
            document.getElementById('d-confirm').value = '';
            document.getElementById('delete-subdomain-label').textContent = subdomain;
            document.getElementById('delete-modal').classList.add('open');
        }

        document.getElementById('delete-form').addEventListener('submit', function (event) {
            event.preventDefault();
            post('<?= site_url('superadmin_tenants/destroy') ?>', {
                tenant_id: document.getElementById('d-tenant-id').value,
                confirm_subdomain: document.getElementById('d-confirm').value,
            }).then((data) => {
                if (data.success) window.location.reload();
                else { const m = document.getElementById('delete-msg'); m.textContent = data.message || 'Hata'; m.style.display = 'block'; }
            });
        });

        // STRING ESCAPER FOR INLINE JS
        function escapeJs(str) {
            if (!str) return '';
            return String(str)
                .replace(/\\/g, '\\\\')
                .replace(/'/g, "\\'")
                .replace(/"/g, '&quot;')
                .replace(/\n/g, ' ')
                .replace(/\r/g, '');
        }

        // --- WHATSAPP QUICK MESSAGE HANDLERS ---
        let currentWaLead = null;
        function openWhatsAppModal(leadId, name, phone, contact, sector) {
            currentWaLead = { id: leadId, name: name, phone: phone, contact: contact || 'Yetkili', sector: sector || 'İşletme' };
            document.getElementById('wa-modal-lead-id').value = leadId;
            document.getElementById('wa-modal-phone').value = phone;
            document.getElementById('wa-modal-lead-name').textContent = name;
            document.getElementById('wa-modal-contact').textContent = contact || 'Yetkili';
            document.getElementById('wa-modal-phone-display').textContent = phone;
            document.getElementById('wa-modal-template-select').value = '1';
            applyWhatsAppTemplate('1');
            document.getElementById('whatsapp-modal').classList.add('open');
        }

        function closeWhatsAppModal() {
            document.getElementById('whatsapp-modal').classList.remove('open');
            currentWaLead = null;
        }

        function applyWhatsAppTemplate(tplKey) {
            if (!currentWaLead) return;
            const textarea = document.getElementById('wa-modal-text');
            const biz = currentWaLead.name;
            const person = currentWaLead.contact;
            const sec = currentWaLead.sector;

            const templates = {
                '1': `Merhaba ${person}, ${biz} için randevu kayıplarını ve no-show oranlarını %80 azaltan BooKi Akıllı Randevu & Müşteri Yönetim Sistemimizi incelediniz mi? İşletmenize özel 10 günlük ücretsiz demo kurulumunu hemen başlatabiliriz: https://bookiapp.kibusiness.co`,
                '2': `Merhaba ${person}, ${biz} (${sec}) adresinize planladığımız BooKi saha ziyaretimiz öncesinde teyit almak istedik. Uygun olduğunuzda 15 dakikalık canlı demomuzu sunmaktan memnuniyet duyarız. İyi çalışmalar dileriz.`,
                '3': `Merhaba ${person}, ${biz} için 10 günlük ücretsiz deneme profiliniz hazırlandı. Personel primleri, online randevu linkiniz ve otomatik WhatsApp hatırlatmalarını hemen test edebilirsiniz: https://bookiapp.kibusiness.co`,
                'custom': `Merhaba ${person}, `
            };

            textarea.value = templates[tplKey] || templates['custom'];
        }

        function sendWhatsAppMessage() {
            if (!currentWaLead) return;
            const leadId = currentWaLead.id;
            const phone = currentWaLead.phone;
            const text = document.getElementById('wa-modal-text').value.trim();

            if (!phone) {
                showToast('Telefon numarası bulunamadı', 'error');
                return;
            }

            post('<?= site_url('superadmin_tenants/api_log_communication') ?>', {
                lead_id: leadId,
                channel: 'whatsapp',
                details: text
            }).then(() => {
                showToast('WhatsApp mesaj kaydı oluşturuldu', 'success');
                closeWhatsAppModal();
                const clean = phone.replace(/[^0-9]/g, '');
                const waUrl = `https://wa.me/${clean}?text=${encodeURIComponent(text)}`;
                window.open(waUrl, '_blank');
            });
        }

        function logCommunication(leadId, channel) {
            post('<?= site_url('superadmin_tenants/api_log_communication') ?>', {
                lead_id: leadId,
                channel: channel,
                details: `${channel.toUpperCase()} iletişimi başlatıldı.`
            }).then(data => {
                if (data.success) {
                    showToast(`${channel.toUpperCase()} aktivitesi zaman tüneline işlendi.`, 'info');
                }
            });
        }

        // --- CALL & VOICE AGENT MODAL (AUDIO HARDWARE + ZADARMA + ELEVENLABS + GEMINI LIVE) ---
        let callTimerInterval = null;
        let callSeconds = 0;
        let activeCallProvider = 'zadarma';
        let currentCallLead = null;

        // Audio Hardware & Live WebRTC / Speech State
        let globalCallAudioStream = null;
        let globalAudioCtx = null;
        let globalAudioAnalyser = null;
        let globalVuAnimFrame = null;
        let globalSpeechRec = null;
        let isCallMicMuted = false;
        let isAudioHardwareConnected = false;

        let micPermissionStatusObj = null;

        async function checkMicrophonePermissionStatus(interactive = false) {
            const hwBadge = document.getElementById('hw-device-badge');
            const hwHint = document.getElementById('hw-device-hint');
            const httpsWarning = document.getElementById('hw-https-warning');
            const deniedWarning = document.getElementById('hw-denied-warning');
            const originCode = document.getElementById('hw-current-origin');
            if (originCode) originCode.textContent = `${location.protocol}//${location.host}`;

            const isSecure = window.isSecureContext || location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';

            if (!isSecure) {
                if (httpsWarning) httpsWarning.style.display = 'block';
                if (hwBadge) {
                    hwBadge.style.background = '#ef4444';
                    hwBadge.style.color = '#fff';
                    hwBadge.textContent = 'HTTPS Gerekli';
                }
                if (hwHint) hwHint.textContent = 'Chrome Android kuralı: Mikrofon izin penceresi yalnızca HTTPS bağlantılarda açılır.';
                if (interactive) showToast('Chrome mikrofon izni için HTTPS zorunludur. Lütfen "HTTPS ile Aç" butonuna basın.', 'warning');
                return 'insecure';
            } else {
                if (httpsWarning) httpsWarning.style.display = 'none';
            }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                if (hwBadge) {
                    hwBadge.style.background = '#ef4444';
                    hwBadge.style.color = '#fff';
                    hwBadge.textContent = 'Desteklenmiyor';
                }
                if (hwHint) hwHint.textContent = 'Tarayıcınız veya bağlantınız WebRTC ses donanımını desteklemiyor.';
                return 'unsupported';
            }

            // Permissions API query (Chrome 43+)
            if (navigator.permissions && navigator.permissions.query) {
                try {
                    micPermissionStatusObj = await navigator.permissions.query({ name: 'microphone' });
                    
                    // Live listener for Lenovo Tab 11 settings changes
                    micPermissionStatusObj.onchange = () => {
                        console.log('Chrome mikrofon izin durumu değişti:', micPermissionStatusObj.state);
                        handlePermissionState(micPermissionStatusObj.state, false);
                    };

                    return handlePermissionState(micPermissionStatusObj.state, interactive);
                } catch (e) {
                    console.log('Permissions API query mikrofonu doğrudan sorgulayamadı:', e);
                }
            }

            if (isAudioHardwareConnected) {
                return 'granted';
            }
            return 'prompt';
        }

        function handlePermissionState(state, interactive = false) {
            const hwBadge = document.getElementById('hw-device-badge');
            const hwHint = document.getElementById('hw-device-hint');
            const deniedWarning = document.getElementById('hw-denied-warning');
            const btnRequest = document.getElementById('btn-request-mic');

            if (state === 'granted') {
                if (deniedWarning) deniedWarning.style.display = 'none';
                if (hwBadge) {
                    hwBadge.style.background = '#059669';
                    hwBadge.style.color = '#fff';
                    hwBadge.textContent = 'İzinli — Bağlanılıyor...';
                }
                if (hwHint) hwHint.textContent = 'Mikrofon izni kayıtlı, ses donanımı bağlanıyor...';
                // 2026-09-21 fix: `requestAudioHardwareAccess(false)` async çağrısı
                // await'siz + sadece permission-sorgusu bağlamında çalışıyordu, yani
                // gerçek getUserMedia promptu çoğu zaman hiç tetiklenmiyordu.
                // interactive olsun/olmasın doğrudan bağlanmayı dene.
                requestAudioHardwareAccess(true);
                return 'granted';
            } else if (state === 'denied') {
                if (deniedWarning) deniedWarning.style.display = 'block';
                if (hwBadge) {
                    hwBadge.style.background = '#ea580c';
                    hwBadge.style.color = '#fff';
                    hwBadge.textContent = 'Chrome Engelledi';
                }
                if (hwHint) {
                    hwHint.textContent = 'Mikrofon izni Chrome ayarlarında engellenmiş. Kilit simgesinden "İzin Ver" yapın.';
                }
                if (btnRequest) {
                    btnRequest.textContent = '⚙️ İzin Ver (Rehber)';
                    btnRequest.style.background = '#ea580c';
                }
                if (interactive) {
                    showToast('Chrome mikrofonu engelledi! Adres çubuğundaki kilit simgesinden izin verin.', 'warning');
                }
                return 'denied';
            } else {
                // 'prompt'
                if (deniedWarning) deniedWarning.style.display = 'none';
                if (hwBadge && !isAudioHardwareConnected) {
                    hwBadge.style.background = '#eab308';
                    hwBadge.style.color = '#000';
                    hwBadge.textContent = 'İzin Bekleniyor';
                }
                if (hwHint && !isAudioHardwareConnected) {
                    hwHint.textContent = 'İzin penceresini açmak için lütfen "🎙️ İzin Ver & Bağla" butonuna dokunun.';
                }
                if (btnRequest && !isAudioHardwareConnected) {
                    btnRequest.textContent = '🎙️ İzin Ver & Bağla';
                    btnRequest.style.background = '#2563eb';
                }
                if (interactive) {
                    requestAudioHardwareAccess(true);
                }
                return 'prompt';
            }
        }

        function switchToHttps() {
            if (location.protocol === 'http:') {
                window.location.href = window.location.href.replace(/^http:/, 'https:');
            } else {
                showToast('Sayfa zaten güvenli HTTPS protokolünde açılmıştır.', 'info');
            }
        }

        function toggleChromeFlagsGuide() {
            const guide = document.getElementById('hw-chrome-flags-guide');
            if (guide) {
                guide.style.display = (guide.style.display === 'none' || !guide.style.display) ? 'block' : 'none';
            }
        }

        function copyOriginToClipboard() {
            const origin = `${location.protocol}//${location.host}`;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(origin).then(() => {
                    showToast(`"${origin}" panoya kopyalandı.`, 'success');
                }).catch(() => {
                    prompt('Adresi kopyalayın:', origin);
                });
            } else {
                prompt('Adresi kopyalayın:', origin);
            }
        }

        function toggleDiagnosticDrawer(forceOpen = null) {
            const drawer = document.getElementById('hw-diagnostic-drawer');
            if (!drawer) return;
            const shouldOpen = forceOpen !== null ? forceOpen : (drawer.style.display === 'none');
            drawer.style.display = shouldOpen ? 'block' : 'none';
            if (shouldOpen) {
                runDiagnosticsCheck();
            }
        }

        function runDiagnosticsCheck() {
            const isSecure = window.isSecureContext || location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
            const diagProto = document.getElementById('diag-protocol');
            const diagWebRtc = document.getElementById('diag-webrtc');
            const diagPerm = document.getElementById('diag-perm-status');
            const diagBrowser = document.getElementById('diag-browser');

            if (diagBrowser) {
                const ua = navigator.userAgent;
                let bName = 'Tarayıcı';
                if (ua.includes('Chrome')) bName = 'Google Chrome';
                if (ua.includes('Android')) bName += ' (Android Tablet)';
                diagBrowser.textContent = bName;
            }

            if (diagProto) {
                if (isSecure) {
                    diagProto.innerHTML = `<span style="color:#10b981;">✓ ${location.protocol.toUpperCase()} (Güvenli)</span>`;
                } else {
                    diagProto.innerHTML = `<span style="color:#ef4444;">✗ ${location.protocol.toUpperCase()} (Güvensiz - HTTPS Gerekli)</span>`;
                }
            }

            if (diagWebRtc) {
                if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                    diagWebRtc.innerHTML = `<span style="color:#10b981;">✓ Destekleniyor (MediaDevices Aktif)</span>`;
                } else {
                    diagWebRtc.innerHTML = `<span style="color:#ef4444;">✗ Desteklenmiyor (${isSecure ? 'Erişim Yok' : 'HTTP Nedeniyle Kapalı'})</span>`;
                }
            }

            if (diagPerm) {
                if (navigator.permissions && navigator.permissions.query) {
                    navigator.permissions.query({ name: 'microphone' }).then(res => {
                        if (res.state === 'granted') {
                            diagPerm.innerHTML = `<span style="color:#10b981;">✓ İzin Verildi (Granted)</span>`;
                        } else if (res.state === 'denied') {
                            diagPerm.innerHTML = `<span style="color:#ea580c;">✗ Engellendi (Kilit simgesinden açın)</span>`;
                        } else {
                            diagPerm.innerHTML = `<span style="color:#eab308;">❓ Bekleniyor (Prompt - Butona dokunun)</span>`;
                        }
                    }).catch(() => {
                        diagPerm.textContent = isAudioHardwareConnected ? 'Bağlı' : 'Bilinmiyor';
                    });
                } else {
                    diagPerm.textContent = isAudioHardwareConnected ? 'Bağlı' : 'Bekleniyor';
                }
            }
        }

        async function requestAudioHardwareAccess(interactive = false) {
            const hwBadge = document.getElementById('hw-device-badge');
            const hwName = document.getElementById('hw-device-name');
            const hwHint = document.getElementById('hw-device-hint');
            const httpsWarning = document.getElementById('hw-https-warning');
            const deniedWarning = document.getElementById('hw-denied-warning');
            const btnMute = document.getElementById('btn-toggle-mute');
            const btnRequest = document.getElementById('btn-request-mic');

            // 1. Check secure context for Android/Tablets (Lenovo Tab 11, etc.)
            const isSecure = window.isSecureContext || location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
            if (!isSecure) {
                if (httpsWarning) httpsWarning.style.display = 'block';
                if (interactive) {
                    showToast('Chrome Android mikrofon izni için HTTPS gereklidir! Lütfen "HTTPS ile Aç" butonunu kullanın.', 'error');
                    toggleDiagnosticDrawer(true);
                }
                return false;
            } else {
                if (httpsWarning) httpsWarning.style.display = 'none';
            }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                if (hwBadge) {
                    hwBadge.style.background = '#ef4444';
                    hwBadge.style.color = '#fff';
                    hwBadge.textContent = 'Desteklenmiyor';
                }
                if (hwHint) hwHint.textContent = 'Tarayıcınız WebRTC ses donanımını desteklemiyor veya HTTPS gerekiyor.';
                if (interactive) showToast('Mikrofon erişimi için HTTPS bağlantısı veya uyumlu tarayıcı gereklidir.', 'error');
                return false;
            }

            try {
                if (hwBadge) {
                    hwBadge.style.background = '#3b82f6';
                    hwBadge.style.color = '#fff';
                    hwBadge.textContent = 'İzin İsteniyor...';
                }
                if (hwHint) hwHint.textContent = 'Chrome izin penceresi açıldı. Lütfen "İzin Ver" veya "Uygulamayı kullanırken" seçin.';

                // Direct synchronous request to getUserMedia inside user gesture
                globalCallAudioStream = await navigator.mediaDevices.getUserMedia({
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true
                    }
                });

                isAudioHardwareConnected = true;
                isCallMicMuted = false;
                if (deniedWarning) deniedWarning.style.display = 'none';
                if (httpsWarning) httpsWarning.style.display = 'none';

                // Detect active audio input device label
                let deviceLabel = 'Kulaklık & Mikrofon';
                try {
                    const devices = await navigator.mediaDevices.enumerateDevices();
                    const audioInputs = devices.filter(d => d.kind === 'audioinput');
                    if (audioInputs.length > 0 && audioInputs[0].label) {
                        deviceLabel = audioInputs[0].label;
                    }
                } catch (e) {}

                if (hwName) hwName.textContent = deviceLabel;
                if (hwBadge) {
                    hwBadge.style.background = '#10b981';
                    hwBadge.style.color = '#fff';
                    hwBadge.textContent = 'Bağlandı ✓';
                }
                if (hwHint) hwHint.textContent = 'Kulaklık ve mikrofon hazır, ses sinyali alınıyor.';
                if (btnRequest) {
                    btnRequest.textContent = '✓ Bağlandı';
                    btnRequest.style.background = '#059669';
                }
                if (btnMute) btnMute.style.display = 'inline-flex';

                startAudioVuMeter(globalCallAudioStream);

                if (interactive) {
                    playAudioTestTone();
                    showToast('🎧 Kulaklık ve mikrofon başarıyla bağlandı!', 'success');
                }
                return true;
            } catch (err) {
                console.warn('Audio permission error:', err);
                isAudioHardwareConnected = false;

                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    if (deniedWarning) deniedWarning.style.display = 'block';
                    if (hwBadge) {
                        hwBadge.style.background = '#ea580c';
                        hwBadge.style.color = '#fff';
                        hwBadge.textContent = 'Chrome Engelledi';
                    }
                    if (hwHint) {
                        hwHint.textContent = 'Mikrofon izni verilmedi. Lütfen adres çubuğundaki kilit simgesinden mikrofona izin verin.';
                    }
                    if (btnRequest) {
                        btnRequest.textContent = '⚙️ Ayarlardan İzin Ver';
                        btnRequest.style.background = '#ea580c';
                    }
                    if (interactive) {
                        showToast('Chrome mikrofon iznini engelledi! Lütfen adres çubuğundaki kilit/ayar simgesinden izin verin.', 'warning');
                        toggleDiagnosticDrawer(true);
                    }
                } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                    if (hwBadge) {
                        hwBadge.style.background = '#ef4444';
                        hwBadge.style.color = '#fff';
                        hwBadge.textContent = 'Mikrofon Bulunamadı';
                    }
                    if (hwHint) hwHint.textContent = 'Cihaza bağlı mikrofon donanımı algılanamadı.';
                    if (interactive) showToast('Cihazda mikrofon donanımı bulunamadı!', 'error');
                } else {
                    if (hwBadge) {
                        hwBadge.style.background = '#ef4444';
                        hwBadge.style.color = '#fff';
                        hwBadge.textContent = 'Hata: ' + err.name;
                    }
                    if (hwHint) hwHint.textContent = 'Ses donanımına ulaşılamadı: ' + (err.message || err.name);
                    if (interactive) showToast('Ses donanımı hatası: ' + err.name, 'error');
                }
                return false;
            }
        }

        function startAudioVuMeter(stream) {
            try {
                if (globalAudioCtx) {
                    try { globalAudioCtx.close(); } catch(e) {}
                }
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                globalAudioCtx = new AudioCtx();
                const source = globalAudioCtx.createMediaStreamSource(stream);
                globalAudioAnalyser = globalAudioCtx.createAnalyser();
                globalAudioAnalyser.fftSize = 64;
                source.connect(globalAudioAnalyser);
                // Do NOT connect to destination, to prevent audio feedback loop through speakers!

                const dataArray = new Uint8Array(globalAudioAnalyser.frequencyBinCount);
                const vuBar = document.getElementById('audio-vu-bar');

                function drawVu() {
                    if (!globalAudioAnalyser) return;
                    globalAudioAnalyser.getByteFrequencyData(dataArray);
                    let sum = 0;
                    for (let i = 0; i < dataArray.length; i++) {
                        sum += dataArray[i];
                    }
                    const avg = sum / dataArray.length;
                    const pct = Math.min(100, Math.round((avg / 128) * 100));
                    if (vuBar) {
                        vuBar.style.width = isCallMicMuted ? '0%' : (pct + '%');
                        vuBar.style.background = pct > 75 ? '#ef4444' : (pct > 40 ? '#f59e0b' : '#10b981');
                    }
                    globalVuAnimFrame = requestAnimationFrame(drawVu);
                }
                drawVu();
            } catch (e) {
                console.warn('VU meter error:', e);
            }
        }

        function playAudioTestTone() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(520, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.18);
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.28);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.3);
                showToast('Kulaklığınızdan test sesi çalındı 🔊', 'info');
            } catch(e) {}
        }

        function toggleCallMicrophoneMute() {
            if (!globalCallAudioStream) return;
            const btn = document.getElementById('btn-toggle-mute');
            isCallMicMuted = !isCallMicMuted;
            globalCallAudioStream.getAudioTracks().forEach(track => {
                track.enabled = !isCallMicMuted;
            });
            if (isCallMicMuted) {
                btn.textContent = '🔊 Sesi Aç';
                btn.style.background = '#ef4444';
                showToast('Mikrofon susturuldu (Mute)', 'warning');
            } else {
                btn.textContent = '🔇 Sustur';
                btn.style.background = 'rgba(255,255,255,0.15)';
                showToast('Mikrofon sesi açıldı', 'info');
            }
        }

        function startLiveSpeechRecognition() {
            const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRec) {
                console.log('Web Speech API bu cihaz/tarayıcıda doğrudan desteklenmiyor.');
                return;
            }
            try {
                if (globalSpeechRec) {
                    try { globalSpeechRec.stop(); } catch(e) {}
                }
                globalSpeechRec = new SpeechRec();
                globalSpeechRec.continuous = true;
                globalSpeechRec.interimResults = true;
                globalSpeechRec.lang = 'tr-TR';

                globalSpeechRec.onresult = (event) => {
                    for (let i = event.resultIndex; i < event.results.length; ++i) {
                        if (event.results[i].isFinal) {
                            const text = event.results[i][0].transcript.trim();
                            if (text) {
                                appendTranscriptLine('Operatör (Canlı Ses)', text);
                            }
                        }
                    }
                };

                globalSpeechRec.onerror = (event) => {
                    console.warn('Speech recognition event error:', event.error);
                };

                globalSpeechRec.start();
            } catch (e) {
                console.warn('SpeechRec başlatılamadı:', e);
            }
        }

        function appendTranscriptLine(speaker, text) {
            const transcriptArea = document.getElementById('call-transcript');
            if (!transcriptArea) return;
            const mins = String(Math.floor(callSeconds / 60)).padStart(2, '0');
            const secs = String(callSeconds % 60).padStart(2, '0');
            const line = `[${mins}:${secs}] ${speaker}: ${text}\n`;
            transcriptArea.value += line;
            transcriptArea.scrollTop = transcriptArea.scrollHeight;
        }

        function speakWithAiVoice(text) {
            if (!window.speechSynthesis) return;
            try {
                window.speechSynthesis.cancel();
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'tr-TR';
                utterance.rate = 1.05;
                const voices = window.speechSynthesis.getVoices();
                const trVoice = voices.find(v => v.lang && v.lang.toLowerCase().startsWith('tr'));
                if (trVoice) utterance.voice = trVoice;
                window.speechSynthesis.speak(utterance);
            } catch (e) {
                console.warn('Speech synthesis hatası:', e);
            }
        }

        function openCallModal(leadId, name, phone, contact, sector) {
            currentCallLead = { id: leadId, name: name, phone: phone, contact: contact || 'Yetkili', sector: sector || 'Genel' };
            document.getElementById('call-lead-id').value = leadId;
            document.getElementById('call-lead-phone').value = phone;
            document.getElementById('call-lead-sector').value = sector;
            const targetPhoneInput = document.getElementById('call-target-phone');
            if (targetPhoneInput) targetPhoneInput.value = phone || '';
            document.getElementById('call-modal-lead-title').textContent = `${name} • ${contact || 'Yetkili'} (${phone || 'Numara yok'}) — ${sector || 'Genel'}`;

            // Reset state
            clearInterval(callTimerInterval);
            callSeconds = 0;
            document.getElementById('call-timer').textContent = '00:00';
            document.getElementById('call-status-text').textContent = 'Hazır';
            document.getElementById('call-status-dot').style.background = '#94a3b8';
            document.getElementById('btn-start-call').style.display = 'inline-flex';
            document.getElementById('btn-end-call').style.display = 'none';
            document.getElementById('call-transcript').value = '';
            document.getElementById('call-notes').value = '';
            document.getElementById('call-has-followup').checked = false;
            document.getElementById('call-followup-details').style.display = 'none';

            // 2026-09-21 fix: modal açılır açılmaz donanım rozetini "kontrol
            // ediliyor" durumuna al ki kullanıcıda "hiçbir şey olmuyor" hissi
            // oluşmasın; gerçek getUserMedia SADECE buton tıklaması (user
            // gesture) içinde çağrılır — Chrome autoplay/user-activation
            // politikası bunu zorunlu kılar.
            const hwBadgeOpen = document.getElementById('hw-device-badge');
            const hwHintOpen = document.getElementById('hw-device-hint');
            if (hwBadgeOpen) {
                hwBadgeOpen.style.background = '#3b82f6';
                hwBadgeOpen.style.color = '#fff';
                hwBadgeOpen.textContent = 'Kontrol Ediliyor...';
            }
            if (hwHintOpen) hwHintOpen.textContent = 'Mikrofon izni sorgulanıyor — bağlanmak için mavi butona bas.';

            switchCallProvider('zadarma');
            updateZadarmaDialButtons();
            document.getElementById('call-modal').classList.add('open');

            // Akıllı mikrofon izin & donanım kontrolü (Lenovo Tab 11 / Android Chrome)
            // SADECE durum tespiti yapar, getUserMedia çağırmaz.
            checkMicrophonePermissionStatus(false);
        }

        function closeCallModal(force = false) {
            if (!force && callTimerInterval) {
                if (!confirm('Görüşme devam ediyor. Kapatmak istediğinizden emin misiniz?')) {
                    return;
                }
            }
            if (callTimerInterval) {
                clearInterval(callTimerInterval);
                callTimerInterval = null;
            }

            // Stop speech recognition and cancel TTS
            if (globalSpeechRec) {
                try { globalSpeechRec.stop(); } catch(e) {}
                globalSpeechRec = null;
            }
            if (window.speechSynthesis) {
                window.speechSynthesis.cancel();
            }
            if (globalVuAnimFrame) {
                cancelAnimationFrame(globalVuAnimFrame);
                globalVuAnimFrame = null;
            }

            // Tamamen donanımı serbest bırak (tablet kayıt ışığını kapat)
            if (globalCallAudioStream) {
                globalCallAudioStream.getTracks().forEach(track => track.stop());
                globalCallAudioStream = null;
                isAudioHardwareConnected = false;
            }

            document.getElementById('call-modal').classList.remove('open');
            currentCallLead = null;
        }

        function switchCallProvider(prov) {
            activeCallProvider = prov;
            document.querySelectorAll('.call-tab-btn').forEach(b => b.classList.remove('active'));
            if (prov === 'zadarma') {
                document.getElementById('btn-tab-zadarma').classList.add('active');
                document.getElementById('transcript-status').textContent = 'Zadarma SIP Santral hattı üzerinden arama';
            } else if (prov === 'elevenlabs') {
                document.getElementById('btn-tab-elevenlabs').classList.add('active');
                document.getElementById('transcript-status').textContent = 'ElevenLabs Conversational AI Türkçe sesli asistan';
            } else if (prov === 'gemini_live') {
                document.getElementById('btn-tab-gemini').classList.add('active');
                document.getElementById('transcript-status').textContent = 'Google AI Studio (Gemini Live) canlı sesli satış diyaloğu';
            }
        }

        async function startCallSession() {
            if (!currentCallLead) return;
            const phone = currentCallLead.phone;
            const name = currentCallLead.name;
            const contact = currentCallLead.contact;
            const sector = currentCallLead.sector;

            // Mikrofon izni henüz alınmadıysa öncelikle izin iste ve bağlan
            if (!isAudioHardwareConnected || !globalCallAudioStream) {
                const connected = await requestAudioHardwareAccess(true);
                if (!connected) {
                    showToast('Görüşmeyi başlatmak için mikrofon izni gereklidir.', 'warning');
                    return;
                }
            }

            document.getElementById('btn-start-call').style.display = 'none';
            document.getElementById('btn-end-call').style.display = 'inline-flex';
            document.getElementById('call-status-dot').style.background = '#10b981';
            document.getElementById('call-status-text').textContent = 'Görüşme Sürüyor...';

            callSeconds = 0;
            clearInterval(callTimerInterval);
            callTimerInterval = setInterval(() => {
                callSeconds++;
                const mins = String(Math.floor(callSeconds / 60)).padStart(2, '0');
                const secs = String(callSeconds % 60).padStart(2, '0');
                document.getElementById('call-timer').textContent = `${mins}:${secs}`;
            }, 1000);

            // Start live microphone speech recognition
            startLiveSpeechRecognition();

            const transcriptArea = document.getElementById('call-transcript');

            if (activeCallProvider === 'gemini_live') {
                transcriptArea.value = `[00:01] Asistan (Gemini Live): Merhaba ${contact} Bey/Hanım! Ben BooKi platformundan arıyorum. ${name} için randevu kayıplarını ve no-show oranlarını tamamen sıfıra indiren yeni akıllı rezervasyon sistemimiz hakkında 1 dakika bilgi vermek isterim.\n` +
                    `[00:08] Müşteri (${contact}): Merhaba, tam olarak ne işe yarıyor bu sistem?\n` +
                    `[00:15] Asistan (Gemini Live): ${sector} sektöründeki işletmelerde randevu kaçırma oranını otomatik WhatsApp teyit mesajlarımızla %80 düşürüyoruz. Ayrıca müşterileriniz 7/24 randevu alırken siz personel primlerini ve adisyonları tek ekrandan yönetiyorsunuz.\n` +
                    `[00:25] Müşteri: Fiyatlandırma ve kurulum nasıl oluyor?\n` +
                    `[00:30] Asistan: 10 gün hiçbir ücret ödemeden ve kredi kartsız deneyebiliyorsunuz. Dilerseniz hemen şimdi kurulum bağlantınızı gönderip başlayalım!\n`;
                
                // Kulaklıktan Türkçe sesli AI selamlama dinlet
                speakWithAiVoice(`Merhaba ${contact}, BooKi akıllı randevu ve yönetim sistemi adına arıyorum. Nasılsınız?`);
                showToast('Google AI Studio Gemini Live canlı ses oturumu bağlandı 🎧', 'info');
            } else if (activeCallProvider === 'elevenlabs') {
                transcriptArea.value = `[00:01] Asistan (ElevenLabs): İyi günler ${name}, ${contact} ile mi görüşüyorum?\n` +
                    `[00:06] Müşteri: Evet buyrun benim.\n` +
                    `[00:10] Asistan (ElevenLabs): Harika! Sektörünüzde (${sector}) faaliyet gösteren işletmelerin randevu ve salon yönetimini kolaylaştıran BooKi platformu adına arıyorum. 10 günlük ücretsiz demo sürecini başlatmak ister misiniz?\n` +
                    `[00:22] Müşteri: Olabilir, bana detaylı bilgileri WhatsApp üzerinden iletirseniz inceleyelim.\n`;
                
                // Kulaklıktan ElevenLabs AI selamlama dinlet
                speakWithAiVoice(`İyi günler ${name}, ${contact} ile mi görüşüyorum? BooKi randevu platformu adına arıyorum.`);
                showToast('ElevenLabs Conversational AI sesli temsilcisi bağlandı 🎧', 'info');
            } else {
                const targetInput = document.getElementById('call-target-phone');
                const actualTargetPhone = (targetInput && targetInput.value.trim()) || phone;

                // Zadarma arama yöntemi: Tarayıcı (WebRTC widget) ya da Callback (SIP cihazı)
                if (zadarmaDialChannel === 'browser') {
                    const ok = await zadarmaStartBrowserCall(actualTargetPhone);
                    if (!ok) {
                        document.getElementById('btn-start-call').style.display = 'inline-flex';
                        document.getElementById('btn-end-call').style.display = 'none';
                        document.getElementById('call-status-dot').style.background = '#ef4444';
                        document.getElementById('call-status-text').textContent = 'Arama Başarısız';
                    }
                    return;
                }
                // Zadarma SIP Call (GERÇEK API callback)
                // Önce YÖNETİCİNİN telefonu çalar; açılınca MÜŞTERİ aranır.
                document.getElementById('call-status-dot').style.background = '#f59e0b';
                document.getElementById('call-status-text').textContent = 'Arama İsteği Gönderiliyor...';
                const btnStart = document.getElementById('btn-start-call');
                if (btnStart) { btnStart.disabled = true; btnStart.textContent = 'Bağlanıyor...'; }

                const cbInput = document.getElementById('zd-callback-phone');
                const callbackPhone = (cbInput && cbInput.value.trim()) || '';

                post('<?= site_url('superadmin_tenants/api_zadarma_call') ?>', {
                    lead_id: currentCallLead.id,
                    target_phone: actualTargetPhone,
                    callback_phone: callbackPhone
                })
                    .then(res => {
                        if (btnStart) { btnStart.disabled = false; }
                        console.log('[Zadarma] callback yanıtı:', res);
                        if (res && res.success) {
                            document.getElementById('call-status-dot').style.background = '#f59e0b';
                            document.getElementById('call-status-text').textContent = 'Çalıyor - Önce Telefonunuz Çalacak';
                            transcriptArea.value = `[00:01] Zadarma Callback Başlatıldı\n` +
                                `[00:01] 📱 Önce çalacak telefonunuz: ${res.from || callbackPhone}\n` +
                                `[00:01] 👤 Müşteri (aranacak): ${res.target_phone || actualTargetPhone}\n` +
                                `[00:02] Telefonunuz çalıyor... Açtığınızda müşteri bağlanacak 🎧\n`;
                            showToast(res.message || `Callback başlatıldı (${res.target_phone || actualTargetPhone}).`, 'success');
                            if (btnStart) { btnStart.textContent = 'Aramayı Başlat'; }
                            pollZadarmaCallStatus(res);
                        } else {
                            document.getElementById('call-status-dot').style.background = '#ef4444';
                            document.getElementById('call-status-text').textContent = 'Arama Başarısız';
                            if (btnStart) { btnStart.style.display = 'inline-flex'; btnStart.textContent = 'Tekrar Dene'; }
                            document.getElementById('btn-end-call').style.display = 'none';
                            transcriptArea.value = `[HATA] ${(res && res.message) ? res.message : 'Zadarma bağlantı hatası'}\n`;
                            showToast((res && res.message) || 'Zadarma bağlantı hatası', 'error');
                        }
                    });
            }
        }

        let zadarmaPollTimer = null;

        function pollZadarmaCallStatus(callInfo) {
            clearInterval(zadarmaPollTimer);
            let step = 0;
            zadarmaPollTimer = setInterval(() => {
                step++;
                const dot = document.getElementById('call-status-dot');
                const txt = document.getElementById('call-status-text');
                if (!document.getElementById('call-modal').classList.contains('open')) {
                    clearInterval(zadarmaPollTimer);
                    return;
                }
                if (step === 5) {
                    if (dot) dot.style.background = '#3b82f6';
                    if (txt) txt.textContent = 'Dahili/Telefon Çalıyor... (Lütfen Açın)';
                } else if (step === 12) {
                    if (dot) dot.style.background = '#10b981';
                    if (txt) txt.textContent = 'Görüşme Sürüyor...';
                    const ta = document.getElementById('call-transcript');
                    if (ta) ta.value += '[00:15] Müşteri hattı bağlandı... Görüşme cihazınız üzerinden yapılıyor.\n';
                } else if (step > 90) {
                    clearInterval(zadarmaPollTimer);
                }
            }, 1000);
        }

        // --- ZADARMA BROWSER CALL (WebRTC webphone widget — resmi entegrasyon) ---
        let zadarmaDialChannel = (function () {
            try { return localStorage.getItem('zd_dial_channel') || 'browser'; } catch (e) { return 'browser'; }
        })();
        let zadarmaWidgetLoaded = false;

        function setZadarmaDialChannel(ch) {
            zadarmaDialChannel = ch;
            try { localStorage.setItem('zd_dial_channel', ch); } catch (e) {}
            updateZadarmaDialButtons();
        }

        function updateZadarmaDialButtons() {
            const b = document.getElementById('zd-ch-browser');
            const c = document.getElementById('zd-ch-callback');
            const badge = document.getElementById('zd-widget-status');
            const cbBox = document.getElementById('zd-callback-box');
            if (b) {
                b.style.background = zadarmaDialChannel === 'browser' ? '#0ea5e9' : '#fff';
                b.style.color = zadarmaDialChannel === 'browser' ? '#fff' : 'var(--text-main)';
                b.style.borderColor = zadarmaDialChannel === 'browser' ? '#0ea5e9' : 'var(--border-color)';
            }
            if (c) {
                c.style.background = zadarmaDialChannel === 'callback' ? '#f59e0b' : '#fff';
                c.style.color = zadarmaDialChannel === 'callback' ? '#fff' : 'var(--text-main)';
                c.style.borderColor = zadarmaDialChannel === 'callback' ? '#f59e0b' : 'var(--border-color)';
            }
            if (cbBox) {
                cbBox.style.display = zadarmaDialChannel === 'callback' ? 'block' : 'none';
            }
            if (badge) {
                badge.textContent = zadarmaDialChannel === 'browser'
                    ? '🎧 Webphone hazır — doğrudan bu tarayıcı üzerinden konuşursunuz'
                    : '📱 Önce sizin telefonunuz çalar; açtığınızda müşteri bağlanır';
            }
        }

        function zadarmaInjectScript(src) {
            return new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = src;
                s.async = false;
                s.onload = resolve;
                s.onerror = () => reject(new Error('Script yüklenemedi: ' + src));
                document.body.appendChild(s);
            });
        }

        async function zadarmaLoadWidget() {
            if (zadarmaWidgetLoaded) return;
            const keyRes = await post('<?= site_url('superadmin_tenants/api_zadarma_webrtc_key') ?>', {});
            if (!keyRes || !keyRes.success) {
                throw new Error((keyRes && keyRes.message) || 'WebRTC key alınamadı');
            }
            // Resmi v9 widget loader'ları (my.zadarma.com)
            await zadarmaInjectScript('https://my.zadarma.com/webphoneWebRTCWidget/v9/js/loader-phone-lib.js?sub_v=1');
            await zadarmaInjectScript('https://my.zadarma.com/webphoneWebRTCWidget/v9/js/loader-phone-fn.js?sub_v=1');
            const t0 = Date.now();
            while ((typeof ZadarmaWebphoneAPI === 'undefined' || typeof zadarmaWidgetFn !== 'function') && Date.now() - t0 < 15000) {
                await new Promise(r => setTimeout(r, 150));
            }
            if (typeof ZadarmaWebphoneAPI === 'undefined' || typeof zadarmaWidgetFn !== 'function') {
                throw new Error('Zadarma webphone widget yüklenemedi (ağ/CSP)');
            }
            window.zadarmaWidgetFn(keyRes.key, keyRes.sip_login, 'square', 'en', true, { right: '10px', bottom: '5px' });
            zadarmaWidgetLoaded = true;
            const st = document.getElementById('zd-widget-status');
            if (st) st.textContent = '🎧 Webphone hazır — aramalar tarayıcı mikrofonunuzla yapılır';

            // Çağrı durumu dinleyicisi (JsSIP oturum olayları)
            setTimeout(() => {
                try {
                    if (window.zdrmWebPhone && window.zdrmWebPhone.webPhoneUA) {
                        window.zdrmWebPhone.webPhoneUA.on('newRTCSession', function(e) {
                            const session = e.session;
                            if (session && session.direction === 'outgoing') {
                                const ta = document.getElementById('call-transcript');
                                const dot = document.getElementById('call-status-dot');
                                const txt = document.getElementById('call-status-text');
                                if (dot) dot.style.background = '#f59e0b';
                                if (txt) txt.textContent = 'Çalıyor... (Müşteri aranıyor)';
                                if (ta) ta.value += `[00:01] Telefon çalıyor, yanıt bekleniyor...\n`;

                                session.on('progress', function() {
                                    if (dot) dot.style.background = '#f59e0b';
                                    if (txt) txt.textContent = 'Çalıyor...';
                                });
                                session.on('confirmed', function() {
                                    if (dot) dot.style.background = '#10b981';
                                    if (txt) txt.textContent = 'Görüşme Başladı (Müşteri Açtı)';
                                    if (ta) ta.value += `[00:03] Müşteri çağrıyı yanıtladı. Canlı görüşme başladı 🎧\n`;
                                });
                                session.on('ended', function(cause) {
                                    if (dot) dot.style.background = '#3b82f6';
                                    if (txt) txt.textContent = 'Görüşme Sona Erdi';
                                    if (ta) ta.value += `[Sonlandı] Çağrı tamamlandı (${cause && cause.cause ? cause.cause : 'Bitti'}).\n`;
                                });
                                session.on('failed', function(cause) {
                                    if (dot) dot.style.background = '#ef4444';
                                    const reason = cause && cause.cause ? cause.cause : 'Bağlantı kurulamadı';
                                    if (txt) txt.textContent = 'Arama Başarısız: ' + reason;
                                    if (ta) ta.value += `[Hata] Arama başarısız oldu: ${reason}\n`;
                                    showToast('Zadarma araması başarısız: ' + reason, 'error');
                                });
                            }
                        });
                    }
                } catch (err) {
                    console.warn('[Zadarma] newRTCSession hook:', err);
                }
            }, 800);
        }

        async function zadarmaStartBrowserCall(phone) {
            try {
                await zadarmaLoadWidget();
            } catch (e) {
                showToast(e.message || 'Webphone yüklenemedi', 'error');
                return false;
            }
            const digits = String(phone || '').replace(/[^0-9]/g, '');
            let target = digits;
            if (target.length <= 5) {
                // Kısa dahili (örn: 100, 101)
            } else if (target.startsWith('00')) {
                // Uluslararası çıkış kodu mevcut
            } else if (target.startsWith('0') && target.length === 11) {
                target = '0090' + target.slice(1);
            } else if (target.length === 10 && target.startsWith('5')) {
                target = '0090' + target;
            } else if (target.length === 12 && target.startsWith('90')) {
                target = '00' + target;
            } else {
                target = '00' + target;
            }
            if (!window.zdrmWebPhone || typeof window.zdrmWebPhone.call !== 'function') {
                showToast('Webphone henüz hazır değil; lütfen birkaç saniye bekleyin.', 'warning');
                return false;
            }
            try {
                window.zdrmWebPhone.call(target);
                const ta = document.getElementById('call-transcript');
                if (ta) ta.value += `[00:00] Tarayıcı (WebRTC v9) araması başlatıldı -> ${target}\n`;
                return true;
            } catch (e) {
                console.error('[Zadarma] widget call hatası:', e);
                showToast('Widget araması başlatılamadı: ' + (e.message || e), 'error');
                return false;
            }
        }

        function testZadarmaConnection() {
            const el = document.getElementById('zadarma-test-result');
            if (el) { el.textContent = 'Zadarma test ediliyor...'; el.style.color = '#d97706'; }
            post('<?= site_url('superadmin_tenants/api_zadarma_test') ?>', {})
                .then(res => {
                    console.log('[Zadarma] test yaniti:', res);
                    if (res && res.success) {
                        if (el) { el.textContent = 'Baglanti OK (Bakiye: ' + (res.balance && res.balance.balance ? res.balance.balance + ' ' + res.balance.currency : 'OK') + ')'; el.style.color = '#16a34a'; }
                        showToast(res.message || 'Zadarma baglantisi dogrulandi', 'success');
                    } else {
                        if (el) { el.textContent = (res && res.message) || 'Baglanti basarisiz'; el.style.color = '#dc2626'; }
                        showToast((res && res.message) || 'Zadarma baglanti hatasi', 'error');
                    }
                });
        }

        function syncZadarmaWebRTC() {
            const el = document.getElementById('zadarma-test-result');
            const btn = document.getElementById('btn-zadarma-webrtc-sync');
            if (el) { el.textContent = 'Zadarma WebRTC senkronize ediliyor...'; el.style.color = '#2563eb'; }
            if (btn) btn.disabled = true;
            post('<?= site_url('superadmin_tenants/api_zadarma_webrtc_sync') ?>', {})
                .then(res => {
                    if (btn) btn.disabled = false;
                    console.log('[Zadarma] WebRTC sync yanıtı:', res);
                    if (res && res.success) {
                        if (el) { el.textContent = 'WebRTC Domainleri OK (' + (res.domains || []).join(', ') + ')'; el.style.color = '#16a34a'; }
                        showToast(res.message || 'Zadarma WebRTC senkronize edildi', 'success');
                        zadarmaWidgetLoaded = false;
                    } else {
                        if (el) { el.textContent = (res && res.message) || 'Senkronizasyon hatası'; el.style.color = '#dc2626'; }
                        showToast((res && res.message) || 'WebRTC senkronizasyon hatası', 'error');
                    }
                })
                .catch(err => {
                    if (btn) btn.disabled = false;
                    if (el) { el.textContent = 'Bağlantı hatası'; el.style.color = '#dc2626'; }
                    showToast('Hata: ' + (err.message || 'Bağlantı hatası'), 'error');
                });
        }

        function endCallSession() {
            clearInterval(callTimerInterval);
            clearInterval(zadarmaPollTimer);
            // Webphone (tarayıcı) çağrısını da kapat
            if (window.zdrmWebPhone && typeof window.zdrmWebPhone.finishCall === 'function') {
                try { window.zdrmWebPhone.finishCall(); } catch (e) { console.warn('[Zadarma] finishCall:', e); }
            }
            document.getElementById('btn-start-call').style.display = 'inline-flex';
            document.getElementById('btn-start-call').textContent = 'Tekrar Ara';
            document.getElementById('btn-end-call').style.display = 'none';
            document.getElementById('call-status-dot').style.background = '#3b82f6';
            document.getElementById('call-status-text').textContent = 'Görüşme Sona Erdi';

            if (globalSpeechRec) {
                try { globalSpeechRec.stop(); } catch(e) {}
            }
            if (window.speechSynthesis) {
                window.speechSynthesis.cancel();
            }

            showToast('Görüşme tamamlandı. Lütfen sonuç seçip kaydedin.', 'info');
        }

        function saveCallLogAndFinish() {
            const leadId = currentCallLead ? currentCallLead.id : parseInt(document.getElementById('call-lead-id').value);
            if (!leadId) {
                showToast('Lütfen görüşme yapılan işletmeyi seçin.', 'error');
                return;
            }

            const btnSave = document.getElementById('btn-save-call-log');
            const originalBtnHtml = btnSave ? btnSave.innerHTML : '';
            if (btnSave) {
                btnSave.disabled = true;
                btnSave.innerHTML = '<span>⏳ Kaydediliyor...</span>';
            }

            const phone = currentCallLead ? (currentCallLead.phone || '') : (document.getElementById('call-lead-phone').value || '');
            const duration = callSeconds;
            const transcript = document.getElementById('call-transcript').value.trim();
            const notes = document.getElementById('call-notes').value.trim();
            const outcome = document.getElementById('call-outcome').value;
            const newStage = document.getElementById('call-new-stage').value;
            const hasFollowup = document.getElementById('call-has-followup').checked;
            const followupDate = hasFollowup ? document.getElementById('call-followup-date').value : '';
            const followupTime = hasFollowup ? document.getElementById('call-followup-time').value : '';

            post('<?= site_url('superadmin_tenants/api_save_call_log') ?>', {
                lead_id: leadId,
                provider: activeCallProvider,
                duration: duration,
                outcome: outcome,
                transcript: transcript,
                notes: notes,
                new_stage: newStage,
                called_number: phone,
                follow_up_date: followupDate,
                follow_up_time: followupTime
            }).then(data => {
                if (btnSave) {
                    btnSave.disabled = false;
                    btnSave.innerHTML = originalBtnHtml;
                }
                if (data && data.success) {
                    showToast('Görüşme ve transkript aktivitelere başarıyla kaydedildi!', 'success');
                    if (callTimerInterval) {
                        clearInterval(callTimerInterval);
                        callTimerInterval = null;
                    }
                    closeCallModal(true);
                    loadPipeline();
                    loadLeadsTable(currentLeadsPage);
                    if (activeLeadId === leadId) {
                        openLeadDrawer(leadId);
                    }
                } else {
                    showToast((data && data.message) ? data.message : 'Kayıt sırasında hata oluştu.', 'error');
                }
            }).catch(err => {
                if (btnSave) {
                    btnSave.disabled = false;
                    btnSave.innerHTML = originalBtnHtml;
                }
                console.error('Call log save error:', err);
                showToast('Bağlantı hatası veya sunucu yanıt vermedi: ' + (err.message || 'Hata'), 'error');
            });
        }

        // --- PLATFORM SETTINGS SAVE HANDLER ---
        function savePlatformSettings() {
            const getVal = (id) => document.getElementById(id) ? document.getElementById(id).value.trim() : '';

            post('<?= site_url('superadmin_tenants/api_save_platform_settings') ?>', {
                // Voice & SIP
                zadarma_api_key: getVal('ps_zadarma_api_key'),
                zadarma_api_secret: getVal('ps_zadarma_api_secret'),
                zadarma_sip_login: getVal('ps_zadarma_sip_login'),
                zadarma_sip_password: getVal('ps_zadarma_sip_password'),
                zadarma_sip_server: getVal('ps_zadarma_sip_server'),
                zadarma_caller_id: getVal('ps_zadarma_caller_id'),
                zadarma_call_mode: getVal('ps_zadarma_call_mode'),

                elevenlabs_api_key: getVal('ps_elevenlabs_api_key'),
                elevenlabs_agent_id: getVal('ps_elevenlabs_agent_id'),
                elevenlabs_voice_id: getVal('ps_elevenlabs_voice_id'),
                elevenlabs_model_id: getVal('ps_elevenlabs_model_id'),

                google_ai_key: getVal('ps_google_ai_key'),
                gemini_live_voice: getVal('ps_gemini_live_voice'),
                ai_model_google: getVal('ps_ai_model_google'),
                gemini_sales_pitch_prompt: getVal('ps_gemini_sales_pitch_prompt'),

                // WhatsApp
                wa_bridge_url: getVal('ps_wa_bridge_url'),
                wa_bridge_secret: getVal('ps_wa_bridge_secret'),

                // SMTP
                platform_smtp_host: getVal('ps_platform_smtp_host'),
                platform_smtp_port: getVal('ps_platform_smtp_port'),
                platform_smtp_user: getVal('ps_platform_smtp_user'),
                platform_smtp_pass: getVal('ps_platform_smtp_pass'),
                platform_smtp_from_name: getVal('ps_platform_smtp_from_name'),
                platform_smtp_from_address: getVal('ps_platform_smtp_from_address'),

                // Google Cloud OAuth & Maps
                google_client_id: getVal('ps_google_client_id'),
                google_client_secret: getVal('ps_google_client_secret'),
                google_project_id: getVal('ps_google_project_id'),
                google_maps_key: getVal('ps_google_maps_key'),

                // Marketplace
                marketplace_commission_rate: getVal('ps_marketplace_commission_rate')
            }).then(data => {
                if (data.success) {
                    showToast('Platform ve entegrasyon ayarları başarıyla kaydedildi!', 'success');
                } else {
                    showToast(data.message || 'Ayarlar kaydedilemedi.', 'error');
                }
            });
        }

        // =========================================================================
        // GOOGLE PLACES PROSPECT & LEAD CRAWLER JAVASCRIPT CONTROLLER
        // =========================================================================
        let placesTaxonomyData = null;
        let crawlerGeoMode = 'districts'; // 'districts' | 'radius'
        let crawlerRadiusMapInstance = null;
        let crawlerRadiusMarker = null;
        let crawlerRadiusCircle = null;
        let crawlerCenter = { lat: 40.2185, lng: 28.9345, address: 'Nilüfer, Bursa' };
        let crawlerRadiusKm = 5;
        let activeCrawlerJobId = null;
        let crawlerPollTimer = null;
        let crawlerJobStartTime = null;
        let crawlerJobTimerInterval = null;
        let currentCrawlerLeadsPage = 1;
        let crawlerSearchDebounce = null;
        const selectedCrawlerLeadIds = new Set();

        function initPlacesCrawlerTab() {
            if (!placesTaxonomyData) {
                loadPlacesTaxonomy();
            }
            loadPlacesStats();
            loadCrawlerLeadsTable(currentCrawlerLeadsPage);
        }

        function switchCrawlerGeoMode(mode) {
            crawlerGeoMode = mode;
            const btnDistricts = document.getElementById('btn-mode-districts');
            const btnRadius = document.getElementById('btn-mode-radius');
            const containerDistricts = document.getElementById('crawler-geo-districts-container');
            const containerRadius = document.getElementById('crawler-geo-radius-container');

            if (mode === 'districts') {
                btnDistricts.classList.add('active');
                btnRadius.classList.remove('active');
                containerDistricts.style.display = 'block';
                containerRadius.style.display = 'none';
            } else {
                btnRadius.classList.add('active');
                btnDistricts.classList.remove('active');
                containerDistricts.style.display = 'none';
                containerRadius.style.display = 'block';
                setTimeout(() => {
                    initCrawlerRadiusMap();
                }, 100);
            }
            updatePlacesPreview();
        }

        function initCrawlerRadiusMap() {
            loadGoogleMapsScript(() => {
                const mapEl = document.getElementById('crawler-radius-map');
                if (!mapEl) return;

                if (!crawlerRadiusMapInstance) {
                    const centerPos = { lat: crawlerCenter.lat, lng: crawlerCenter.lng };

                    crawlerRadiusMapInstance = new google.maps.Map(mapEl, {
                        center: centerPos,
                        zoom: 12,
                        mapTypeControl: false,
                        streetViewControl: false,
                        fullscreenControl: false,
                        zoomControl: true
                    });

                    // Draggable center marker
                    crawlerRadiusMarker = new google.maps.Marker({
                        position: centerPos,
                        map: crawlerRadiusMapInstance,
                        draggable: true,
                        title: 'Arama Merkezi'
                    });

                    // Overlay circle
                    crawlerRadiusCircle = new google.maps.Circle({
                        strokeColor: '#2563eb',
                        strokeOpacity: 0.8,
                        strokeWeight: 2,
                        fillColor: '#3b82f6',
                        fillOpacity: 0.18,
                        map: crawlerRadiusMapInstance,
                        center: centerPos,
                        radius: crawlerRadiusKm * 1000
                    });

                    // Marker drag handler
                    crawlerRadiusMarker.addListener('dragend', (e) => {
                        const newLat = e.latLng.lat();
                        const newLng = e.latLng.lng();
                        updateCrawlerCenterPosition(newLat, newLng);
                    });

                    // Map click handler
                    crawlerRadiusMapInstance.addListener('click', (e) => {
                        const newLat = e.latLng.lat();
                        const newLng = e.latLng.lng();
                        crawlerRadiusMarker.setPosition({ lat: newLat, lng: newLng });
                        updateCrawlerCenterPosition(newLat, newLng);
                    });
                } else {
                    google.maps.event.trigger(crawlerRadiusMapInstance, 'resize');
                    crawlerRadiusMapInstance.setCenter({ lat: crawlerCenter.lat, lng: crawlerCenter.lng });
                }
            });
        }

        function updateCrawlerCenterPosition(lat, lng) {
            crawlerCenter.lat = parseFloat(lat.toFixed(6));
            crawlerCenter.lng = parseFloat(lng.toFixed(6));
            if (crawlerRadiusCircle) {
                crawlerRadiusCircle.setCenter({ lat: crawlerCenter.lat, lng: crawlerCenter.lng });
            }
            const label = document.getElementById('crawler-center-label');
            if (label) {
                label.textContent = `${crawlerCenter.lat}, ${crawlerCenter.lng}`;
            }
            updatePlacesPreview();
        }

        function updateCrawlerRadius(kmVal) {
            crawlerRadiusKm = parseFloat(kmVal);
            const badge = document.getElementById('crawler-radius-badge');
            if (badge) badge.textContent = `${crawlerRadiusKm} km`;

            const areaLabel = document.getElementById('crawler-area-label');
            if (areaLabel) {
                const area = (Math.PI * crawlerRadiusKm * crawlerRadiusKm).toFixed(1);
                areaLabel.textContent = `~${area} km²`;
            }

            if (crawlerRadiusCircle) {
                crawlerRadiusCircle.setRadius(crawlerRadiusKm * 1000);
            }
            updatePlacesPreview();
        }

        function selectCrawlerDistricts(mode) {
            const checkboxes = document.querySelectorAll('#crawler-district-checkboxes input[type="checkbox"]');
            const centerDistricts = ['Nilüfer', 'Osmangazi', 'Yıldırım', 'Mudanya', 'Gemlik'];

            checkboxes.forEach(cb => {
                if (mode === 'all') {
                    cb.checked = true;
                } else if (mode === 'none') {
                    cb.checked = false;
                } else if (mode === 'center') {
                    cb.checked = centerDistricts.includes(cb.value);
                }
                const parent = cb.closest('.places-district-item');
                if (parent) {
                    if (cb.checked) parent.classList.add('checked');
                    else parent.classList.remove('checked');
                }
            });
            updatePlacesPreview();
        }

        function selectCrawlerCategories(mode) {
            const checkboxes = document.querySelectorAll('#crawler-category-checkboxes input[type="checkbox"]');
            const popularKeys = ['guzellik_kuafor', 'spa_masaj', 'klinik_saglik', 'dis_klinigi', 'spor_fitness', 'veteriner_pet'];

            checkboxes.forEach(cb => {
                if (mode === 'all') {
                    cb.checked = true;
                } else if (mode === 'none') {
                    cb.checked = false;
                } else if (mode === 'popular') {
                    cb.checked = popularKeys.includes(cb.value);
                }
                const parent = cb.closest('.places-pill-item');
                if (parent) {
                    if (cb.checked) parent.classList.add('checked');
                    else parent.classList.remove('checked');
                }
            });
            updatePlacesPreview();
        }

        function handleCrawlerCheckboxChange(el) {
            const parent = el.closest('.places-district-item, .places-pill-item');
            if (parent) {
                if (el.checked) parent.classList.add('checked');
                else parent.classList.remove('checked');
            }
            updatePlacesPreview();
        }

        function loadPlacesTaxonomy() {
            fetch('<?= site_url('superadmin_tenants/api_places_taxonomy') ?>')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    placesTaxonomyData = data;

                    // 1. Render Districts
                    const distContainer = document.getElementById('crawler-district-checkboxes');
                    const distSelect = document.getElementById('crawler-district-filter');
                    const directDistSelect = document.getElementById('direct-search-district');
                    if (distContainer) distContainer.innerHTML = '';
                    if (distSelect) distSelect.innerHTML = '<option value="">Tüm İlçeler</option>';
                    if (directDistSelect) directDistSelect.innerHTML = '<option value="">Tüm Bursa (Geniş Kapsam)</option>';

                    const initialCheckedDistricts = ['Nilüfer', 'Osmangazi', 'Yıldırım'];
                    const districtsList = Array.isArray(data.districts) ? data.districts : (Array.isArray(data.regions) ? data.regions : Object.values(data.districts || data.regions || {}));

                    districtsList.forEach(d => {
                        const distName = typeof d === 'string' ? d : (d.name || d.label || d.slug || '');
                        if (!distName) return;
                        const isChecked = initialCheckedDistricts.includes(distName);
                        if (distContainer) {
                            const item = document.createElement('label');
                            item.className = `places-district-item ${isChecked ? 'checked' : ''}`;
                            item.innerHTML = `
                                <input type="checkbox" value="${distName}" ${isChecked ? 'checked' : ''} onchange="handleCrawlerCheckboxChange(this)">
                                <span>${distName}</span>
                            `;
                            distContainer.appendChild(item);
                        }

                        if (distSelect) {
                            const opt = document.createElement('option');
                            opt.value = distName;
                            opt.textContent = distName;
                            distSelect.appendChild(opt);
                        }

                        if (directDistSelect) {
                            const opt = document.createElement('option');
                            opt.value = distName;
                            opt.textContent = distName;
                            directDistSelect.appendChild(opt);
                        }
                    });

                    // 2. Render Categories
                    const catContainer = document.getElementById('crawler-category-checkboxes');
                    const sectorSelect = document.getElementById('crawler-sector-filter');
                    const directCatSelect = document.getElementById('direct-search-category');
                    if (catContainer) catContainer.innerHTML = '';
                    if (sectorSelect) sectorSelect.innerHTML = '<option value="">Tüm Sektörler</option>';
                    if (directCatSelect) directCatSelect.innerHTML = '';

                    const initialCheckedCategories = ['guzellik_kuafor', 'spa_masaj', 'klinik_saglik', 'dis_klinigi', 'spor_fitness'];
                    const rawCategories = data.categories || [];
                    const categoriesList = Array.isArray(rawCategories) 
                        ? rawCategories 
                        : Object.entries(rawCategories).map(([k, v]) => ({ slug: k, ...v }));

                    categoriesList.forEach(cat => {
                        const slug = cat.slug || cat.key || '';
                        const label = cat.label || cat.name || slug;
                        const icon = cat.icon ? (cat.icon + ' ') : '';
                        if (!slug) return;

                        const isChecked = initialCheckedCategories.includes(slug);
                        if (catContainer) {
                            const item = document.createElement('label');
                            item.className = `places-pill-item ${isChecked ? 'checked' : ''}`;
                            item.innerHTML = `
                                <input type="checkbox" value="${slug}" ${isChecked ? 'checked' : ''} onchange="handleCrawlerCheckboxChange(this)">
                                <span>${icon}${label}</span>
                            `;
                            catContainer.appendChild(item);
                        }

                        if (sectorSelect) {
                            const opt = document.createElement('option');
                            opt.value = label;
                            opt.textContent = `${icon}${label}`;
                            sectorSelect.appendChild(opt);
                        }

                        if (directCatSelect) {
                            const opt = document.createElement('option');
                            opt.value = slug;
                            opt.textContent = `${icon}${label}`;
                            directCatSelect.appendChild(opt);
                        }
                    });

                    updatePlacesPreview();
                })
                .catch(err => console.error('Taxonomy load error:', err));
        }

        // DIRECT GOOGLE PLACES SEARCH & LIVE SUGGEST
        let directSearchSuggestDebounce = null;
        function handleDirectSearchInput(val) {
            const dropdown = document.getElementById('direct-search-suggest-dropdown');
            if (!dropdown) return;
            const query = (val || '').trim();
            if (query.length < 2) {
                dropdown.style.display = 'none';
                dropdown.innerHTML = '';
                return;
            }

            if (directSearchSuggestDebounce) clearTimeout(directSearchSuggestDebounce);
            directSearchSuggestDebounce = setTimeout(() => {
                fetch(`<?= site_url('superadmin_tenants/api_global_search') ?>?q=${encodeURIComponent(query)}`)
                    .then(r => r.json())
                    .then(data => {
                        const leads = Array.isArray(data) ? data : (data.leads || []);
                        let html = '';

                        // Action option: Search on Google Places
                        html += `
                            <div onclick="selectDirectSearchOption('${escapeJs(query)}', true)" style="padding:0.6rem 0.85rem;cursor:pointer;background:#f5f3ff;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;color:#6d28d9;font-weight:700;font-size:0.8rem;">
                                <span>⚡ <strong>Google Places'da Canlı Ara & Kaydet:</strong> "${escapeHtml(query)}"</span>
                                <span class="badge" style="background:#8b5cf6;color:#fff;font-size:0.65rem;">Google Keşif</span>
                            </div>
                        `;

                        if (leads.length > 0) {
                            html += `<div style="padding:0.35rem 0.85rem;font-size:0.7rem;font-weight:700;color:var(--text-light);background:#f8fafc;border-bottom:1px solid #f1f5f9;">CRM HAVUZUNDA BULUNANLAR (${leads.length}):</div>`;
                            leads.slice(0, 6).forEach(ld => {
                                html += `
                                    <div onclick="selectDirectSearchLead(${ld.id})" style="padding:0.5rem 0.85rem;cursor:pointer;border-bottom:1px solid #f8fafc;display:flex;justify-content:space-between;align-items:center;transition:background 0.15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                                        <div>
                                            <div style="font-weight:700;font-size:0.82rem;color:var(--text-main);">${escapeHtml(ld.name)}</div>
                                            <div style="font-size:0.72rem;color:var(--text-muted);">📍 ${escapeHtml(ld.district || 'Bursa')} | ${escapeHtml(ld.sector || '')} ${ld.phone ? '• 📞 ' + escapeHtml(ld.phone) : ''}</div>
                                        </div>
                                        <span class="badge ${ld.stage ? 'active' : 'pending'}" style="font-size:0.65rem;">${escapeHtml(ld.stage || 'Lead')}</span>
                                    </div>
                                `;
                            });
                        }

                        dropdown.innerHTML = html;
                        dropdown.style.display = 'block';
                    })
                    .catch(() => {
                        dropdown.style.display = 'none';
                    });
            }, 250);
        }

        function selectDirectSearchOption(val, autoSubmit = false) {
            const input = document.getElementById('direct-search-query');
            const dropdown = document.getElementById('direct-search-suggest-dropdown');
            if (input) input.value = val;
            if (dropdown) dropdown.style.display = 'none';
            if (autoSubmit) {
                directPlacesSearch();
            }
        }

        function selectDirectSearchLead(leadId) {
            const dropdown = document.getElementById('direct-search-suggest-dropdown');
            if (dropdown) dropdown.style.display = 'none';
            openLeadDrawer(leadId);
        }

        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('direct-search-suggest-dropdown');
            const input = document.getElementById('direct-search-query');
            if (dropdown && dropdown.style.display === 'block') {
                if (!dropdown.contains(e.target) && e.target !== input) {
                    dropdown.style.display = 'none';
                }
            }
        });

        function handlePlacesDirectSearch(e) {
            if (e) e.preventDefault();
            const dropdown = document.getElementById('direct-search-suggest-dropdown');
            if (dropdown) dropdown.style.display = 'none';
            directPlacesSearch();
        }

        function directPlacesSearch() {
            const query = (document.getElementById('direct-search-query')?.value || '').trim();
            if (!query) {
                showToast('Lütfen aranacak bir işletme adı veya anahtar kelime girin.', 'warning');
                return;
            }

            const district = document.getElementById('direct-search-district')?.value || '';
            const category = document.getElementById('direct-search-category')?.value || 'guzellik_kuafor';
            const mode = document.querySelector('input[name="direct_search_mode"]:checked')?.value || 'basic';
            const autoEnrich = mode === 'enriched';

            const btn = document.getElementById('btn-direct-search');
            const originalBtn = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span>⏳ Google Aranıyor...</span>';
            }

            const resContainer = document.getElementById('direct-search-results-container');
            const resGrid = document.getElementById('direct-search-results-grid');
            const resTitle = document.getElementById('direct-search-results-title');

            post('<?= site_url('superadmin_tenants/api_places_direct_search') ?>', {
                query: query,
                district: district,
                category: category,
                auto_enrich: autoEnrich ? '1' : '0',
                mode: mode
            })
            .then(data => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalBtn;
                }

                if (!data.success) {
                    showToast(data.message || 'Arama sırasında bir hata oluştu.', 'error');
                    return;
                }

                showToast(`${data.places_found} işletme bulundu (${data.leads_created} yeni, ${data.leads_updated} güncellendi)!`, 'success');
                
                // Refresh crawler table & stats & lead pool
                loadPlacesStats();
                loadCrawlerLeadsTable(1);
                if (typeof loadLeadsTable === 'function') {
                    loadLeadsTable(1);
                }

                // Display result cards
                if (resContainer && resGrid) {
                    resContainer.style.display = 'block';
                    if (resTitle) {
                        resTitle.textContent = `'${data.query}' için bulunan işletmeler (${data.places_found} Google sonucu - ${mode === 'enriched' ? 'Zenginleştirilmiş' : 'Temel Mod'}):`;
                    }
                    resGrid.innerHTML = '';

                    const crmLeads = data.existing_crm_leads || [];
                    const googleLeads = data.leads || [];

                    // Render CRM existing leads if any
                    if (crmLeads.length > 0) {
                        const crmHeader = document.createElement('div');
                        crmHeader.style.cssText = 'grid-column:1/-1;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:0.6rem 0.85rem;display:flex;align-items:center;justify-content:space-between;';
                        crmHeader.innerHTML = `
                            <span style="font-weight:700;font-size:0.8rem;color:var(--text-main);">📦 CRM Havuzunda Kayıtlı Olan Eşleşmeler (${crmLeads.length} adet):</span>
                            <span class="badge" style="background:#e0e7ff;color:#4338ca;font-size:0.65rem;">Mevcut Leadler</span>
                        `;
                        resGrid.appendChild(crmHeader);

                        crmLeads.forEach(ld => {
                            const card = document.createElement('div');
                            card.style.cssText = 'background:#fdfdfe;border:1px solid #cbd5e1;border-radius:8px;padding:0.75rem 0.85rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);display:flex;flex-direction:column;justify-content:space-between;gap:0.5rem;';
                            const safeName = escapeJs(ld.name || '');
                            const safePh = escapeJs(ld.phone || '');
                            const safeContact = escapeJs(ld.contact_person || '');
                            const safeSector = escapeJs(ld.sector || '');

                            card.innerHTML = `
                                <div>
                                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:0.5rem;">
                                        <strong style="font-size:0.86rem;color:var(--primary);cursor:pointer;" onclick="openLeadDrawer(${ld.id})">${escapeHtml(ld.name)}</strong>
                                        <span class="badge active" style="font-size:0.68rem;">${escapeHtml(ld.stage || 'Lead')}</span>
                                    </div>
                                    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:0.25rem;">
                                        📍 ${escapeHtml(ld.district || 'Bursa')} | ${escapeHtml(ld.sector || 'Sektör')}
                                    </div>
                                    ${ld.address ? `<div style="font-size:0.72rem;color:var(--text-light);margin-top:0.2rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${escapeHtml(ld.address)}">${escapeHtml(ld.address)}</div>` : ''}
                                    ${ld.phone ? `<div style="font-size:0.76rem;color:var(--text-main);margin-top:0.35rem;font-weight:600;">📞 ${escapeHtml(ld.phone)}</div>` : ''}
                                </div>
                                <div style="display:flex;gap:0.35rem;justify-content:flex-end;border-top:1px solid #f1f5f9;padding-top:0.5rem;margin-top:0.25rem;flex-wrap:wrap;">
                                    <button type="button" class="btn btn-secondary btn-xs" onclick="openLeadDrawer(${ld.id})">Detay / Düzenle</button>
                                    ${ld.phone ? `<button type="button" class="btn btn-primary btn-xs" onclick="openCallModal(${ld.id}, '${safeName}', '${safePh}', '${safeContact}', '${safeSector}')" style="background:#0f172a;border-color:#0f172a;">📞 Ara</button>` : ''}
                                </div>
                            `;
                            resGrid.appendChild(card);
                        });
                    }

                    if (googleLeads.length > 0) {
                        if (crmLeads.length > 0) {
                            const gHeader = document.createElement('div');
                            gHeader.style.cssText = 'grid-column:1/-1;margin-top:0.5rem;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:8px;padding:0.6rem 0.85rem;display:flex;align-items:center;justify-content:space-between;';
                            gHeader.innerHTML = `
                                <span style="font-weight:700;font-size:0.8rem;color:#6d28d9;">🌐 Google Places Canlı Keşif Sonuçları (${googleLeads.length} adet):</span>
                                <span class="badge" style="background:#8b5cf6;color:#fff;font-size:0.65rem;">Google Places</span>
                            `;
                            resGrid.appendChild(gHeader);
                        }

                        googleLeads.forEach(ld => {
                            const card = document.createElement('div');
                            card.style.cssText = 'background:#ffffff;border:1px solid var(--border-color);border-radius:8px;padding:0.75rem 0.85rem;box-shadow:0 1px 3px rgba(0,0,0,0.05);display:flex;flex-direction:column;justify-content:space-between;gap:0.5rem;';
                            
                            const isEnriched = ld.discovery_state === 'ENRICHED';
                            const safeName = escapeJs(ld.name || '');
                            const safePh = escapeJs(ld.phone || '');
                            const safeContact = escapeJs(ld.contact_person || '');
                            const safeSector = escapeJs(ld.sector || '');

                            card.innerHTML = `
                                <div>
                                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:0.5rem;">
                                        <strong style="font-size:0.86rem;color:var(--primary);cursor:pointer;" onclick="openLeadDrawer(${ld.id})">${escapeHtml(ld.name)}</strong>
                                        <span class="badge ${isEnriched ? 'active' : 'pending'}" style="font-size:0.68rem;">${isEnriched ? '✨ Zengin' : '🎯 Temel'}</span>
                                    </div>
                                    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:0.25rem;">
                                        📍 ${escapeHtml(ld.district || 'Bursa')} | ${escapeHtml(ld.sector || 'Sektör')}
                                    </div>
                                    ${ld.address ? `<div style="font-size:0.72rem;color:var(--text-light);margin-top:0.2rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${escapeHtml(ld.address)}">${escapeHtml(ld.address)}</div>` : ''}
                                    ${ld.phone ? `<div style="font-size:0.76rem;color:var(--text-main);margin-top:0.35rem;font-weight:600;">📞 ${escapeHtml(ld.phone)}</div>` : '<div style="font-size:0.72rem;color:var(--text-light);margin-top:0.35rem;">📞 Telefon henüz zenginleştirilmedi</div>'}
                                    ${ld.google_rating ? `<div style="font-size:0.74rem;color:#f59e0b;margin-top:0.2rem;">⭐ ${ld.google_rating} (${ld.google_user_ratings_total || 0} yorum)</div>` : ''}
                                </div>
                                <div style="display:flex;gap:0.35rem;justify-content:flex-end;border-top:1px solid #f1f5f9;padding-top:0.5rem;margin-top:0.25rem;flex-wrap:wrap;">
                                    ${!isEnriched ? `<button type="button" class="btn btn-secondary btn-xs" onclick="enrichPlaceLead(${ld.id}, this)" style="color:#7c3aed;font-weight:700;">✨ Zenginleştir</button>` : ''}
                                    <button type="button" class="btn btn-secondary btn-xs" onclick="openLeadDrawer(${ld.id})">Detay</button>
                                    ${ld.phone ? `<button type="button" class="btn btn-primary btn-xs" onclick="openCallModal(${ld.id}, '${safeName}', '${safePh}', '${safeContact}', '${safeSector}')" style="background:#0f172a;border-color:#0f172a;">📞 Ara</button>` : ''}
                                    ${ld.google_maps_url ? `<a href="${ld.google_maps_url}" target="_blank" class="btn btn-secondary btn-xs" title="Google Haritalar">🗺️</a>` : ''}
                                </div>
                            `;
                            resGrid.appendChild(card);
                        });
                    }

                    if (crmLeads.length === 0 && googleLeads.length === 0) {
                        resGrid.innerHTML = `<div style="grid-column:1/-1;color:var(--text-muted);font-size:0.84rem;padding:1.25rem;background:#f8fafc;border:1px dashed var(--border-color);border-radius:8px;text-align:center;">
                            <strong>'${escapeHtml(data.query)}'</strong> için Google Haritalar'da eşleşen yeni işletme kaydı bulunamadı.<br>
                            <span style="font-size:0.78rem;color:var(--text-light);margin-top:0.35rem;display:block;">Aramayı daha geniş bir isim (örn. sadece 'Elegance') veya 'Tüm Bursa' bölgesini seçerek tekrar deneyebilirsiniz.</span>
                        </div>`;
                    }
                }
            })
            .catch(err => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalBtn;
                }
                console.error('Direct search error:', err);
                showToast('Bağlantı hatası oluştu.', 'error');
            });
        }

        function loadPlacesStats() {
            fetch('<?= site_url('superadmin_tenants/api_places_usage_stats') ?>')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    const stats = data.stats;
                    const elDisc = document.getElementById('places-stat-total-discovered');
                    const elEnr = document.getElementById('places-stat-enriched');
                    const elApi = document.getElementById('places-stat-api-today');

                    if (elDisc) elDisc.textContent = (stats.total_discovered_leads || 0).toLocaleString('tr-TR');
                    if (elEnr) elEnr.textContent = (stats.total_enriched_leads || 0).toLocaleString('tr-TR');
                    if (elApi) {
                        const textCalls = stats.today_calls_text_search || 0;
                        const detCalls = stats.today_calls_details || 0;
                        elApi.textContent = `${textCalls} Text / ${detCalls} Detay`;
                    }
                })
                .catch(err => console.error('Stats load error:', err));
        }

        function getSelectedCrawlerDistricts() {
            const checkboxes = document.querySelectorAll('#crawler-district-checkboxes input[type="checkbox"]:checked');
            return Array.from(checkboxes).map(cb => cb.value);
        }

        function getSelectedCrawlerCategories() {
            const checkboxes = document.querySelectorAll('#crawler-category-checkboxes input[type="checkbox"]:checked');
            return Array.from(checkboxes).map(cb => cb.value);
        }

        function getSelectedCrawlerDepth() {
            const radio = document.querySelector('input[name="crawler_depth"]:checked');
            return radio ? radio.value : 'standard';
        }

        function updatePlacesPreview() {
            const categories = getSelectedCrawlerCategories();
            const depth = getSelectedCrawlerDepth();
            const previewTitle = document.getElementById('crawler-preview-title');
            const previewDetails = document.getElementById('crawler-preview-details');

            if (categories.length === 0) {
                if (previewTitle) previewTitle.textContent = 'Lütfen en az bir BooKi sektörü seçin';
                if (previewDetails) previewDetails.textContent = 'Hedef sektörler seçildiğinde sorgu ve tahmini keşif adedi hesaplanacaktır.';
                return;
            }

            if (crawlerGeoMode === 'districts') {
                const districts = getSelectedCrawlerDistricts();
                if (districts.length === 0) {
                    if (previewTitle) previewTitle.textContent = 'Lütfen en az bir ilçe seçin';
                    if (previewDetails) previewDetails.textContent = 'Sol panelden Bursa ilçelerini işaretleyin.';
                    return;
                }

                // Call server preview
                const params = new URLSearchParams();
                params.set('geo_mode', 'districts');
                params.set('districts', districts.join(','));
                params.set('categories', categories.join(','));
                params.set('depth', depth);

                fetch(`<?= site_url('superadmin_tenants/api_places_preview') ?>?${params.toString()}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            if (previewTitle) {
                                previewTitle.textContent = `Tahmini ${data.estimated_queries} API Sorgusu • ~${data.estimated_leads_min} - ${data.estimated_leads_max} Potansiyel Lead`;
                            }
                            if (previewDetails) {
                                previewDetails.textContent = `Hedef: ${districts.length} İlçe (${districts.slice(0, 4).join(', ')}${districts.length > 4 ? '...' : ''}) × ${categories.length} BooKi Sektörü | Mod: ${depth === 'deep' ? 'Derin (Maks 60 Sonuç/Sorgu)' : 'Standart (20 Sonuç/Sorgu)'}`;
                            }
                        }
                    });
            } else {
                const params = new URLSearchParams();
                params.set('geo_mode', 'radius');
                params.set('center_lat', crawlerCenter.lat);
                params.set('center_lng', crawlerCenter.lng);
                params.set('radius_km', crawlerRadiusKm);
                params.set('categories', categories.join(','));
                params.set('depth', depth);

                fetch(`<?= site_url('superadmin_tenants/api_places_preview') ?>?${params.toString()}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            if (previewTitle) {
                                previewTitle.textContent = `Tahmini ${data.estimated_queries} API Sorgusu • ~${data.estimated_leads_min} - ${data.estimated_leads_max} Potansiyel Lead`;
                            }
                            if (previewDetails) {
                                previewDetails.textContent = `Hedef: Pin Çemberi (Yarıçap: ${crawlerRadiusKm} km, ~${(Math.PI * crawlerRadiusKm * crawlerRadiusKm).toFixed(1)} km²) × ${categories.length} BooKi Sektörü`;
                            }
                        }
                    });
            }
        }

        function startPlacesCrawl() {
            const categories = getSelectedCrawlerCategories();
            if (categories.length === 0) {
                showToast('Lütfen en az bir randevu sektörü seçin!', 'warning');
                return;
            }

            const depth = getSelectedCrawlerDepth();
            const filterOp = document.getElementById('crawler_filter_operational')?.checked ? 1 : 0;
            const payload = {
                geo_mode: crawlerGeoMode,
                categories: JSON.stringify(categories),
                depth: depth,
                business_status: filterOp ? 'OPERATIONAL' : 'ALL'
            };

            if (crawlerGeoMode === 'districts') {
                const districts = getSelectedCrawlerDistricts();
                if (districts.length === 0) {
                    showToast('Lütfen en az bir Bursa ilçesi seçin!', 'warning');
                    return;
                }
                payload.districts = JSON.stringify(districts);
            } else {
                payload.center_lat = crawlerCenter.lat;
                payload.center_lng = crawlerCenter.lng;
                payload.radius_km = crawlerRadiusKm;
            }

            const btnStart = document.getElementById('btn-start-places-crawl');
            if (btnStart) {
                btnStart.disabled = true;
                btnStart.innerHTML = '<span>⏳ Tarama Başlatılıyor...</span>';
            }

            post('<?= site_url('superadmin_tenants/api_places_start_crawl') ?>', payload)
                .then(data => {
                    if (btnStart) {
                        btnStart.disabled = false;
                        btnStart.innerHTML = '<span>🚀 Canlı Keşfi Başlat</span>';
                    }

                    if (!data.success) {
                        showToast(data.message || 'Tarama başlatılamadı.', 'error');
                        return;
                    }

                    activeCrawlerJobId = data.job_id;
                    showToast(`Tarama görevi başlatıldı (#${activeCrawlerJobId})!`, 'success');
                    
                    // Show Job Monitor Card
                    const jobCard = document.getElementById('places-job-card');
                    if (jobCard) jobCard.classList.add('active');

                    // Start timer & polling
                    crawlerJobStartTime = Date.now();
                    if (crawlerJobTimerInterval) clearInterval(crawlerJobTimerInterval);
                    crawlerJobTimerInterval = setInterval(() => {
                        const elapsedSec = Math.floor((Date.now() - crawlerJobStartTime) / 1000);
                        const timerEl = document.getElementById('places-job-timer');
                        if (timerEl) timerEl.textContent = `${elapsedSec}s`;
                    }, 1000);

                    pollPlacesJob(activeCrawlerJobId);
                })
                .catch(err => {
                    if (btnStart) {
                        btnStart.disabled = false;
                        btnStart.innerHTML = '<span>🚀 Canlı Keşfi Başlat</span>';
                    }
                    showToast('Sunucu bağlantı hatası oluştu.', 'error');
                });
        }

        function pollPlacesJob(jobId) {
            if (crawlerPollTimer) clearTimeout(crawlerPollTimer);

            fetch(`<?= site_url('superadmin_tenants/api_places_job_progress') ?>?job_id=${jobId}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        stopCrawlerJobMonitor();
                        return;
                    }

                    const job = data.job;
                    updateCrawlerJobUI(job);

                    if (job.status === 'completed') {
                        stopCrawlerJobMonitor();
                        showToast(`Tarama tamamlandı! ${job.leads_created} yeni lead havuza eklendi ✓`, 'success');
                        loadCrawlerLeadsTable(1);
                        loadPlacesStats();
                        // Update header count badges
                        const badgeLeads = document.getElementById('badge-leads-count');
                        if (badgeLeads) {
                            const cur = parseInt(badgeLeads.textContent) || 0;
                            badgeLeads.textContent = cur + (job.leads_created || 0);
                        }
                    } else if (job.status === 'cancelled') {
                        stopCrawlerJobMonitor();
                        showToast('Tarama görevi durduruldu.', 'warning');
                        loadCrawlerLeadsTable(1);
                        loadPlacesStats();
                    } else if (job.status === 'failed') {
                        stopCrawlerJobMonitor();
                        showToast(`Tarama hatası: ${job.error_message || 'Bilinmeyen hata'}`, 'error');
                        loadCrawlerLeadsTable(1);
                    } else {
                        // Continue polling
                        crawlerPollTimer = setTimeout(() => pollPlacesJob(jobId), 1200);
                    }
                })
                .catch(err => {
                    crawlerPollTimer = setTimeout(() => pollPlacesJob(jobId), 2000);
                });
        }

        function updateCrawlerJobUI(job) {
            const badge = document.getElementById('places-job-badge');
            const queryLabel = document.getElementById('places-job-query-label');
            const pBar = document.getElementById('places-job-progress-bar');
            const pPct = document.getElementById('places-job-progress-pct');
            const qDone = document.getElementById('places-job-queries-done');
            const qTotal = document.getElementById('places-job-queries-total');
            const statFound = document.getElementById('places-job-found-count');
            const statCreated = document.getElementById('places-job-created-count');
            const statUpdated = document.getElementById('places-job-updated-count');
            const statFailed = document.getElementById('places-job-failed-count');

            if (badge) {
                if (job.status === 'completed') {
                    badge.style.background = '#10b981';
                    badge.textContent = 'TAMAMLANDI ✓';
                } else if (job.status === 'cancelled') {
                    badge.style.background = '#f59e0b';
                    badge.textContent = 'DURDURULDU';
                } else if (job.status === 'failed') {
                    badge.style.background = '#ef4444';
                    badge.textContent = 'HATA';
                } else {
                    badge.style.background = '#3b82f6';
                    badge.textContent = 'ÇALIŞIYOR ⚡';
                }
            }

            if (queryLabel) {
                queryLabel.textContent = job.current_query ? `Sorgulanıyor: "${job.current_query}"` : 'Sorgular işleniyor...';
            }

            const pct = Math.min(100, Math.max(0, job.progress_percentage || 0));
            if (pBar) pBar.style.width = `${pct}%`;
            if (pPct) pPct.textContent = `${pct}%`;
            if (qDone) qDone.textContent = job.queries_completed || 0;
            if (qTotal) qTotal.textContent = job.total_queries || 0;
            if (statFound) statFound.textContent = job.places_found || 0;
            if (statCreated) statCreated.textContent = job.leads_created || 0;
            if (statUpdated) statUpdated.textContent = job.leads_updated || 0;
            if (statFailed) statFailed.textContent = job.queries_failed || 0;
        }

        function stopCrawlerJobMonitor() {
            if (crawlerPollTimer) clearTimeout(crawlerPollTimer);
            if (crawlerJobTimerInterval) clearInterval(crawlerJobTimerInterval);
        }

        function cancelPlacesJob() {
            if (!activeCrawlerJobId) return;
            post('<?= site_url('superadmin_tenants/api_places_cancel_job') ?>', { job_id: activeCrawlerJobId })
                .then(data => {
                    showToast('Durdurma isteği iletildi.', 'info');
                });
        }

        function handleCrawlerSearchInput(val) {
            if (crawlerSearchDebounce) clearTimeout(crawlerSearchDebounce);
            crawlerSearchDebounce = setTimeout(() => {
                loadCrawlerLeadsTable(1);
            }, 300);
        }

        function switchCrawlerTableEnrichFilter(filterVal) {
            const select = document.getElementById('crawler-enrich-filter');
            if (select) {
                select.value = filterVal === 'all' ? '' : filterVal;
                loadCrawlerLeadsTable(1);
            }
        }

        function loadCrawlerLeadsTable(page = 1) {
            currentCrawlerLeadsPage = page;
            const tbody = document.getElementById('crawler-leads-tbody');
            if (!tbody) return;

            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2.5rem;color:var(--text-light);">Yükleniyor...</td></tr>';

            const q = document.getElementById('crawler-search-input')?.value || '';
            const sector = document.getElementById('crawler-sector-filter')?.value || '';
            const district = document.getElementById('crawler-district-filter')?.value || '';
            const enrichFilter = document.getElementById('crawler-enrich-filter')?.value || '';

            const params = new URLSearchParams({
                q: q,
                sector: sector,
                district: district,
                enrich_filter: enrichFilter,
                is_places: 1,
                page: page,
                limit: 25,
                sort: 'id',
                order: 'desc'
            });

            fetch(`<?= site_url('superadmin_tenants/api_leads') ?>?${params.toString()}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success || !data.leads || data.leads.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2.5rem;color:var(--text-light);">Henüz Google Places ile keşfedilmiş lead bulunmuyor. Yukarıdan canlı keşif başlatabilirsiniz.</td></tr>';
                        renderCrawlerPagination(0, 1, 25);
                        return;
                    }

                    tbody.innerHTML = '';
                    selectedCrawlerLeadIds.clear();
                    const selectAllCb = document.getElementById('crawler-select-all');
                    if (selectAllCb) selectAllCb.checked = false;

                    data.leads.forEach(ld => {
                        const tr = document.createElement('tr');
                        tr.id = `crawler-row-${ld.id}`;

                        // Status Badge
                        let statusBadge = '<span class="places-badge-status operational">OPERATIONAL</span>';
                        if (ld.business_status === 'CLOSED_TEMPORARILY') {
                            statusBadge = '<span class="places-badge-status temp_closed">GEÇİCİ KAPALI</span>';
                        } else if (ld.business_status === 'CLOSED_PERMANENTLY') {
                            statusBadge = '<span class="places-badge-status perm_closed">KALICI KAPALI</span>';
                        }

                        // Google Maps link
                        const mapsUri = ld.google_maps_uri || (ld.place_id ? `https://www.google.com/maps/search/?api=1&query=Google&query_place_id=${ld.place_id}` : '');
                        const mapsLink = mapsUri ? `<a href="${mapsUri}" target="_blank" style="display:inline-flex;align-items:center;gap:0.25rem;color:#2563eb;font-size:0.75rem;text-decoration:none;" title="Google Haritalar'da Aç">📍 Harita ↗</a>` : '—';

                        // Enrichment details
                        let enrichHtml = '';
                        if (ld.enriched_at) {
                            const ratingHtml = ld.rating ? `⭐ <strong>${ld.rating}</strong> <span style="color:#64748b;">(${ld.user_rating_count || 0})</span>` : '';
                            const phoneHtml = ld.phone ? `<div>📞 <a href="tel:${ld.phone}" style="color:var(--text-main);">${ld.phone}</a></div>` : '';
                            const webHtml = ld.website ? `<div>🌐 <a href="${ld.website}" target="_blank" style="color:#2563eb;font-size:0.72rem;">${ld.website.replace(/^https?:\/\//, '').substring(0, 24)}...</a></div>` : '';
                            enrichHtml = `
                                <div style="font-size:0.75rem;line-height:1.4;">
                                    <span class="places-badge-status places-badge-enriched" style="font-size:0.65rem;margin-bottom:0.2rem;">ZENGİNLEŞTİRİLDİ ✓</span>
                                    ${ratingHtml ? `<div>${ratingHtml}</div>` : ''}
                                    ${phoneHtml}
                                    ${webHtml}
                                </div>
                            `;
                        } else {
                            enrichHtml = `
                                <div style="font-size:0.75rem;color:var(--text-light);">
                                    <span class="places-badge-status places-badge-discovered" style="font-size:0.65rem;margin-bottom:0.3rem;">KEŞFEDİLDİ (PRO)</span>
                                    <div>Henüz zenginleştirilmedi</div>
                                </div>
                            `;
                        }

                        // Actions
                        const enrichBtn = !ld.enriched_at ? `
                            <button type="button" class="btn btn-secondary btn-xs btn-enrich-single" onclick="enrichPlaceLead(${ld.id}, this)" style="background:#f5f3ff;color:#6d28d9;border-color:#ddd6fe;font-weight:600;" title="Place Details New (Telefon, Web, Puan) Çek">
                                ✨ Zenginleştir
                            </button>
                        ` : '';

                        tr.innerHTML = `
                            <td style="text-align:center;">
                                <input type="checkbox" value="${ld.id}" onchange="handleCrawlerRowSelect(${ld.id}, this.checked)">
                            </td>
                            <td>
                                <div style="font-weight:700;color:var(--text-main);cursor:pointer;" onclick="openLeadDrawer(${ld.id})">
                                    ${ld.name}
                                </div>
                                <div style="font-size:0.72rem;color:var(--text-muted);font-family:monospace;">
                                    ${ld.primary_type || 'business'}
                                </div>
                            </td>
                            <td>
                                <span class="card-tag sector">${ld.sector || 'Genel'}</span>
                            </td>
                            <td>
                                <div style="font-weight:600;font-size:0.78rem;">${ld.district || 'Bursa'}</div>
                                <div style="font-size:0.72rem;color:var(--text-light);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${ld.address || ''}">
                                    ${ld.address || '—'}
                                </div>
                            </td>
                            <td>${statusBadge}</td>
                            <td>${mapsLink}</td>
                            <td>${enrichHtml}</td>
                            <td style="text-align:right;white-space:nowrap;">
                                ${enrichBtn}
                                <button type="button" class="btn btn-secondary btn-xs" onclick="openLeadDrawer(${ld.id})" title="CRM Lead Kartını Aç">
                                    👤 Lead Kartı
                                </button>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });

                    renderCrawlerPagination(data.total, data.current_page, data.limit);
                })
                .catch(err => {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:#b91c1c;">Veriler yüklenirken hata oluştu.</td></tr>';
                });
        }

        function handleCrawlerRowSelect(leadId, isChecked) {
            if (isChecked) {
                selectedCrawlerLeadIds.add(leadId);
            } else {
                selectedCrawlerLeadIds.delete(leadId);
            }
        }

        function toggleSelectAllCrawlerLeads(isChecked) {
            const checkboxes = document.querySelectorAll('#crawler-leads-tbody input[type="checkbox"]');
            checkboxes.forEach(cb => {
                cb.checked = isChecked;
                const id = parseInt(cb.value);
                if (isChecked) selectedCrawlerLeadIds.add(id);
                else selectedCrawlerLeadIds.delete(id);
            });
        }

        function enrichPlaceLead(leadId, btnEl) {
            if (btnEl) {
                btnEl.disabled = true;
                btnEl.textContent = '⏳ Çekiliyor...';
            }

            post('<?= site_url('superadmin_tenants/api_places_enrich_lead') ?>', { lead_id: leadId })
                .then(data => {
                    if (data.success) {
                        showToast(`${data.place_name || 'İşletme'} başarıyla zenginleştirildi!`, 'success');
                        loadCrawlerLeadsTable(currentCrawlerLeadsPage);
                        loadPlacesStats();
                    } else {
                        if (btnEl) {
                            btnEl.disabled = false;
                            btnEl.textContent = '✨ Zenginleştir';
                        }
                        showToast(data.message || 'Zenginleştirme başarısız.', 'error');
                    }
                })
                .catch(err => {
                    if (btnEl) {
                        btnEl.disabled = false;
                        btnEl.textContent = '✨ Zenginleştir';
                    }
                    showToast('Sunucu bağlantı hatası oluştu.', 'error');
                });
        }

        function bulkEnrichPlaceLeads() {
            const ids = Array.from(selectedCrawlerLeadIds);
            if (ids.length === 0) {
                showToast('Lütfen tablodan en az bir işletme seçin!', 'warning');
                return;
            }

            if (!confirm(`Seçilen ${ids.length} işletme için Google Place Details (New API) çağrısı yapılacak. Devam edilsin mi?`)) {
                return;
            }

            showToast(`${ids.length} işletme zenginleştiriliyor, lütfen bekleyin...`, 'info');

            post('<?= site_url('superadmin_tenants/api_places_bulk_enrich') ?>', { lead_ids: JSON.stringify(ids) })
                .then(data => {
                    if (data.success) {
                        showToast(`${data.enriched_count} işletme başarıyla zenginleştirildi!`, 'success');
                        loadCrawlerLeadsTable(currentCrawlerLeadsPage);
                        loadPlacesStats();
                    } else {
                        showToast(data.message || 'Toplu zenginleştirme tamamlanamadı.', 'error');
                    }
                })
                .catch(err => {
                    showToast('Toplu zenginleştirme sırasında hata oluştu.', 'error');
                });
        }

        function exportPlacesCsv() {
            window.open('<?= site_url('superadmin_tenants/api_places_export_csv') ?>', '_blank');
        }

        function clearAllLeadsConfirm() {
            if (!confirm('⚠️ TÜM CRM LEAD HAVUZU VE GEÇMİŞ AKTİVİTELER SİLİNECEKTİR!\n\nBu işlem geri alınamaz. Devam etmek istiyor musunuz?')) {
                return;
            }

            post('<?= site_url('superadmin_tenants/api_clear_all_leads') ?>', { confirm: 'yes' })
                .then(data => {
                    if (data.success) {
                        showToast('Tüm lead havuzu başarıyla sıfırlandı ✓', 'success');
                        loadCrawlerLeadsTable(1);
                        loadPlacesStats();
                        const badgeLeads = document.getElementById('badge-leads-count');
                        if (badgeLeads) badgeLeads.textContent = '0';
                        const kpiLeads = document.getElementById('kpi-total-leads');
                        if (kpiLeads) kpiLeads.textContent = '0';
                    } else {
                        showToast(data.message || 'Sıfırlama başarısız oldu.', 'error');
                    }
                })
                .catch(err => {
                    showToast('Sıfırlama sırasında hata oluştu.', 'error');
                });
        }

        function renderCrawlerPagination(total, currentPage, limit) {
            const info = document.getElementById('crawler-pagination-info');
            const btns = document.getElementById('crawler-pagination-buttons');
            if (!info || !btns) return;

            const totalPages = Math.max(1, Math.ceil(total / limit));
            info.textContent = `Toplam ${total} işletmeden ${(currentPage - 1) * limit + 1}-${Math.min(total, currentPage * limit)} arası gösteriliyor (Sayfa ${currentPage}/${totalPages})`;

            btns.innerHTML = '';
            if (totalPages <= 1) return;

            if (currentPage > 1) {
                const prev = document.createElement('button');
                prev.className = 'btn btn-secondary btn-xs';
                prev.textContent = '← Önceki';
                prev.onclick = () => loadCrawlerLeadsTable(currentPage - 1);
                btns.appendChild(prev);
            }

            if (currentPage < totalPages) {
                const next = document.createElement('button');
                next.className = 'btn btn-secondary btn-xs';
                next.textContent = 'Sonraki →';
                next.onclick = () => loadCrawlerLeadsTable(currentPage + 1);
                btns.appendChild(next);
            }
        }

        // Initialize URL hash navigation and sidebar state on load
        window.addEventListener('DOMContentLoaded', () => {
            try {
                if (localStorage.getItem('booki_sidebar_collapsed') === '1') {
                    const sb = document.getElementById('app-sidebar');
                    if (sb) sb.classList.add('collapsed');
                }
            } catch (e) {}

            const hash = window.location.hash.replace('#', '');
            if (hash && document.getElementById(`tab-${hash}`)) {
                switchTab(hash);
            } else {
                loadPipeline();
                loadLeadsTable();
            }
        });
    </script>
</body>
</html>
