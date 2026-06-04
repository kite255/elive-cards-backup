
<style>
    .footer {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background-color: #ffffff;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        padding: 12px 0;
        z-index: 1000;
    }
    .nav-container {
        display: flex;
        justify-content: space-around;
        align-items: center;
        max-width: 600px;
        margin: 0 auto;
    }
    .nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        color: #666;
        transition: color 0.3s;
    }
    .nav-item:hover {
        color: #161461;
    }
    .nav-item svg {
        width: 24px;
        height: 24px;
        margin-bottom: 4px;
    }
    .nav-label {
        font-size: 12px;
        font-weight: 500;
    }
</style>

<div class="footer">
    <div class="nav-container">
        <a href="{{route('invitees-statistics', $event->code)}}" class="nav-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <span class="nav-label">Statistics</span>
        </a>
        <a href="{{route('scan-cards.index', $event->code)}}" class="nav-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2m0 0H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="nav-label">Scan</span>
        </a>
        <a href="{{route('invitees-list', $event->code)}}" class="nav-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <span class="nav-label">Invitees</span>
        </a>
    </div>
</div>
