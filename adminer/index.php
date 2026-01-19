<?php
/**
 * LogicPanel Adminer Wrapper
 * Adds dark/light mode toggle and custom styling
 */

// Include the original Adminer
function adminer_object()
{
    class LogicPanelAdminer extends Adminer
    {
        function head()
        {
            // Inject theme toggle script
            ?>
            <style>
                /* Theme toggle injection */
                #theme-toggle {
                    position: fixed;
                    top: 15px;
                    right: 15px;
                    z-index: 10000;
                    background: var(--lp-bg-card, #2d313a);
                    border: 1px solid var(--lp-border, #3e4249);
                    border-radius: 8px;
                    padding: 8px 12px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    color: var(--lp-text-primary, #e4e6eb);
                    font-size: 14px;
                    transition: all 0.3s ease;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
                }

                #theme-toggle:hover {
                    transform: translateY(-1px);
                }
            </style>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    // Create theme toggle button
                    const toggle = document.createElement('button');
                    toggle.id = 'theme-toggle';
                    toggle.innerHTML = `
                        <svg id="theme-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="5"></circle>
                            <line x1="12" y1="1" x2="12" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="23"></line>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                            <line x1="1" y1="12" x2="3" y2="12"></line>
                            <line x1="21" y1="12" x2="23" y2="12"></line>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                        </svg>
                        <svg id="theme-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                        <span id="theme-text">Light</span>
                    `;
                    document.body.appendChild(toggle);

                    // Get saved theme or default to dark
                    const savedTheme = localStorage.getItem('adminer-theme') || 'dark';
                    applyTheme(savedTheme);

                    // Toggle theme on click
                    toggle.addEventListener('click', function () {
                        const currentTheme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
                        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                        applyTheme(newTheme);
                        localStorage.setItem('adminer-theme', newTheme);
                    });

                    function applyTheme(theme) {
                        const sunIcon = document.getElementById('theme-icon-sun');
                        const moonIcon = document.getElementById('theme-icon-moon');
                        const themeText = document.getElementById('theme-text');

                        if (theme === 'dark') {
                            document.body.classList.add('dark-mode');
                            document.documentElement.setAttribute('data-theme', 'dark');
                            sunIcon.style.display = 'none';
                            moonIcon.style.display = 'block';
                            themeText.textContent = 'Dark';
                        } else {
                            document.body.classList.remove('dark-mode');
                            document.documentElement.setAttribute('data-theme', 'light');
                            sunIcon.style.display = 'block';
                            moonIcon.style.display = 'none';
                            themeText.textContent = 'Light';
                        }
                    }
                });
            </script>
            <?php
            return true; // Use default head
        }

        function name()
        {
            return 'LogicPanel DB Manager';
        }
    }

    return new LogicPanelAdminer;
}

include './adminer-4.8.1.php';
