<!-- ═══════════════ NAVBAR ═══════════════ -->
    <nav class="navbar">

        <a href="index.php" class="nav-logo">Blog<span>Forge</span></a>

        <ul class="nav-menu">
            <li><a href="index.php">Home</a></li>
            <li><a href="blogs.php">Blogs</a></li>

            <?php if(isset($_SESSION["user_id"])){  ?>
           
            <li><a href="write.php">Write</a></li>

            <?php } ?>
            
        </ul>


        <?php if(isset($_SESSION["user_id"])) { ?>

        <div class="nav-right">


            <div class="nav-user" id="navUser" onclick="toggleDropdown()">
                <div class="nav-user-avatar" id="navAvatar"><?php echo strtoupper(substr($_SESSION["user_name"], 0, 2)) ?></div>
                <span class="nav-username" id="navUsername"><?php echo strtoupper($_SESSION["user_name"]); ?></span>
                <svg class="nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="6,9 12,15 18,9"/>
                </svg>
                <div class="nav-dropdown">
                <a href="profile.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                    Profile
                </a>
                <a href="logout.php" class="danger">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Logout
                </a>
                </div>
            </div>

            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>

        </div>

        <?php }else{ ?>

        <ul class="nav-right">
            <li style="list-style-type: none;">
                <a style="text-decoration: none;color: white;opacity: 0.8;" href="login.php">Login
            </a>
            </li>
        </ul>

        <?php } ?>


    </nav>
