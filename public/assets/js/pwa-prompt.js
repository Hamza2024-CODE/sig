/**
 * SGFEP PWA Custom Install Prompt Banner
 * Displays a premium custom floating install prompt for Mobile & Desktop.
 */
(function() {
    let deferredPrompt = null;
    
    // Check if the application is already running in standalone mode (already installed)
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches 
        || window.navigator.standalone 
        || document.referrer.includes('android-app://');
        
    if (isStandalone) {
        console.log('SGFEP PWA: Running in standalone mode.');
        return;
    }

    // Capture the beforeinstallprompt event
    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent Chrome 76 and later from showing the default mini-infobar
        e.preventDefault();
        // Stash the event so it can be triggered later
        deferredPrompt = e;
        
        // Don't show the prompt if it was dismissed during the current session
        if (sessionStorage.getItem('pwa_install_prompt_dismissed') === 'true') {
            console.log('SGFEP PWA: Install banner was previously dismissed in this session.');
            return;
        }

        // Delay showing the banner slightly for a better user experience
        setTimeout(showPWABanner, 2000);
    });

    function showPWABanner() {
        // If banner already exists, do nothing
        if (document.getElementById('pwa-install-banner')) return;

        // Inject Styles dynamically
        const style = document.createElement('style');
        style.id = 'pwa-banner-styles';
        style.innerHTML = `
            .pwa-install-banner {
                position: fixed;
                bottom: 24px;
                left: 50%;
                transform: translateX(-50%);
                width: 90%;
                max-width: 480px;
                background: rgba(255, 255, 255, 0.85);
                backdrop-filter: blur(20px) saturate(180%);
                -webkit-backdrop-filter: blur(20px) saturate(180%);
                border: 1px solid rgba(255, 255, 255, 0.4);
                box-shadow: 0 12px 40px rgba(0, 56, 112, 0.15);
                border-radius: 20px;
                padding: 1rem 1.25rem;
                z-index: 999999;
                direction: rtl;
                font-family: 'Cairo', sans-serif;
                animation: pwaSlideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }
            [data-theme="dark"] .pwa-install-banner {
                background: rgba(15, 23, 42, 0.85);
                border: 1px solid rgba(255, 255, 255, 0.08);
                box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
            }
            .pwa-banner-content {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .pwa-icon-wrapper {
                flex-shrink: 0;
                width: 48px;
                height: 48px;
                background: #003870;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                box-shadow: 0 4px 10px rgba(0, 56, 112, 0.2);
            }
            .pwa-app-icon {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .pwa-text-info {
                flex-grow: 1;
            }
            .pwa-title {
                margin: 0 0 2px 0;
                font-size: 0.95rem;
                font-weight: 700;
                color: #003870;
            }
            [data-theme="dark"] .pwa-title {
                color: #38bdf8;
            }
            .pwa-desc {
                margin: 0;
                font-size: 0.78rem;
                color: #475569;
                line-height: 1.4;
            }
            [data-theme="dark"] .pwa-desc {
                color: #94a3b8;
            }
            .pwa-actions {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-shrink: 0;
            }
            .btn-pwa-action {
                border: none;
                outline: none;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            .btn-pwa-install {
                background: linear-gradient(135deg, #003870 0%, #0284c7 100%);
                color: white;
                padding: 6px 14px;
                border-radius: 50px;
                font-size: 0.82rem;
                font-weight: 700;
                box-shadow: 0 4px 12px rgba(0, 56, 112, 0.2);
            }
            .btn-pwa-install:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 16px rgba(0, 56, 112, 0.3);
            }
            .btn-pwa-close {
                background: rgba(0, 0, 0, 0.05);
                color: #64748b;
                width: 28px;
                height: 28px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.85rem;
            }
            [data-theme="dark"] .btn-pwa-close {
                background: rgba(255, 255, 255, 0.08);
                color: #94a3b8;
            }
            .btn-pwa-close:hover {
                background: rgba(239, 68, 68, 0.1);
                color: #ef4444;
            }
            @keyframes pwaSlideUp {
                from {
                    transform: translate(-50%, 120%);
                    opacity: 0;
                }
                to {
                    transform: translate(-50%, 0);
                    opacity: 1;
                }
            }
            @keyframes pwaSlideDown {
                from {
                    transform: translate(-50%, 0);
                    opacity: 1;
                }
                to {
                    transform: translate(-50%, 120%);
                    opacity: 0;
                }
            }
            @media (max-width: 480px) {
                .pwa-install-banner {
                    bottom: 16px;
                    padding: 0.85rem;
                }
                .pwa-desc {
                    display: none;
                }
            }
        `;
        document.head.appendChild(style);

        // Resolve PWA Icon dynamically from page tags
        let iconUrl = '/assets/icons/icon-72x72.png';
        const appleIcon = document.querySelector('link[rel="apple-touch-icon"]');
        if (appleIcon && appleIcon.href) {
            iconUrl = appleIcon.href;
        } else {
            const manifestLink = document.querySelector('link[rel="manifest"]');
            if (manifestLink && manifestLink.href) {
                const manifestBase = manifestLink.href.substring(0, manifestLink.href.lastIndexOf('/'));
                iconUrl = manifestBase + '/assets/icons/icon-72x72.png';
            }
        }

        // Create Banner DOM node
        const banner = document.createElement('div');
        banner.id = 'pwa-install-banner';
        banner.className = 'pwa-install-banner';
        banner.innerHTML = `
            <div class="pwa-banner-content">
                <div class="pwa-icon-wrapper">
                    <img src="${iconUrl}" alt="منصة تسيير" class="pwa-app-icon">
                </div>
                <div class="pwa-text-info">
                    <h4 class="pwa-title">تثبيت تطبيق منصة تسيير</h4>
                    <p class="pwa-desc">قم بتثبيت التطبيق للوصول السريع وتصفح أسرع للمنصة.</p>
                </div>
                <div class="pwa-actions">
                    <button id="pwa-btn-install" class="btn-pwa-action btn-pwa-install">تثبيت الآن</button>
                    <button id="pwa-btn-close" class="btn-pwa-action btn-pwa-close"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
        `;
        document.body.appendChild(banner);

        // Bind Install Action
        document.getElementById('pwa-btn-install').addEventListener('click', async () => {
            if (!deferredPrompt) return;
            
            // Trigger native prompt
            deferredPrompt.prompt();
            
            // Wait for choice outcome
            const { outcome } = await deferredPrompt.userChoice;
            console.log(`SGFEP PWA: User choice outcome is ${outcome}`);
            
            deferredPrompt = null;
            dismissBanner();
        });

        // Bind Close Action
        document.getElementById('pwa-btn-close').addEventListener('click', () => {
            // Dismiss banner for current session
            sessionStorage.setItem('pwa_install_prompt_dismissed', 'true');
            dismissBanner();
        });
    }

    function dismissBanner() {
        const banner = document.getElementById('pwa-install-banner');
        if (banner) {
            banner.style.animation = 'pwaSlideDown 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards';
            setTimeout(() => {
                banner.remove();
                const style = document.getElementById('pwa-banner-styles');
                if (style) style.remove();
            }, 400);
        }
    }

    // Dismiss banner immediately if app is successfully installed
    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        dismissBanner();
        console.log('SGFEP PWA: App was successfully installed.');
    });
})();
