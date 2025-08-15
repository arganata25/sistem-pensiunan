            </main>
        </div> 
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const hamburgerBtn = document.getElementById('hamburger-btn');
            const sidebar = document.getElementById('sidebar');

            hamburgerBtn.addEventListener('click', function () {
                sidebar.classList.toggle('-translate-x-full');
            });
        });
    </script>
</body>
</html>
