<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portifolio</title>
    <link rel="stylesheet" href="./assets/css/portifolio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@iconscout/unicons@4.0.8/css/line.min.css">
</head>
<body>
    <!-- nave menue -->
     <div class="container">
        <nav id="header">
            <div class="nav-logo">
                <p class="nav-name">BC</p>
            </div>
            <div class="nav-menu" id="myNavMenu">
                <ul class="nav_menu_lists">
                    <li class="nav_list">
                        <a href="#home" class="nav-link active-link">Home</a>
                        <div class="circle"></div>
                    </li>
                    <li class="nav_list">
                        <a href="#about" class="nav-link">About</a>
                        <div class="circle"></div>
                    </li>
                    <li class="nav_list">
                        <a href="#projects" class="nav-link">Projects</a>
                        <div class="circle"></div>
                    </li>
                    <li class="nav_list">
                        <a href="#contact" class="nav-link">Contact</a>
                        <div class="circle"></div>
                    </li>
                </ul>
            </div>
            <!-- dark mode -->
             <div class="mode">
                <div class="moon-sun" id="toggle-switch">
                    <i class="fa-solid fa-moon" id="moon"></i>
                    <i class="fa-solid fa-sun" id="sun"></i>
                </div>
             </div>
             <div class="nav-menu-btn">
             <i class="fa-solid fa-bars" onclick="myMenuFunction()"></i>
                <!-- <i class="uil uil-bars" ></i> -->
             </div>
        </nav>

        <!-- main section -->
         <main class="wrapper">
            <section class="featured-box" id="home">
                <div class="featured-text">
                    <div class="hello">
                        <p>Hello I,m</p>
                    </div>
                    <div class="featured-name">
                        <span class="typedtext"></span>
                    </div>
                    <div class="text-info">
                        <p>My name is Emmanuel! I,m a web developer with 1 + year experience</p>
                    </div>
                    <div class="text-btn">
                        <button class="btn hire-btn">Hire me</button>
                        <button class="btn">Download CV <i class="uil uil-file"></i></button>
                    </div>
                    <div class="social_icons">
                        <div class="icon_circle"></div>
                        <div class="icon"><a href="https://instagram.com/call_mi_burnaclout" target="_blank"><i class="uil uil-instagram"></i></a></div>
                        <div class="icon"><a href="https://facebook.com/burnaclout" target="_blank"><i class="uil uil-facebook"></i></a></div>
                        <div class="icon"><a href="https://linkedin.com/in/burna-clout-52474930" target="_blank"><i class="uil uil-linkedin-alt"></i></a></div>
                        <div class="icon"><a href="https://x.com/burnaclout616" target="_blank"><i class="uil uil-twitter-alt"></i></a></div>
                        <div class="icon"><a href="https://github.com/AndriodBurna" target="_blank"><i class="uil uil-github-alt"></i></a></div>
                    </div>
                </div>
                <div class="featured-image">
                    <div class="image">
                        <img src="./assets/images/me.jpg" alt="">
                    </div>
                </div>
            </section>
            <!-- about box -->
             <section class="section" id="about">
                <div class="top-header">
                    <h1>About me</h1>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="about-info">
                            <figure class="about-me">
                                <figcaption>
                                    <img src="./assets/images/me.jpg" alt="" class="profile" />
                                    <h2>Web developer</h2>
                                    <p>My name is Kalibbala Emmanuel! I,m a web developer with 1 + year of experience</p>
                                
                            </figure>
                            <button class="about-me-btn">Hire Me</button>
                        </div>
                    </div>
                    <div class="skill">
                        <div class="skill-box">
                            <span class="title">HTML</span>
                            <div class="skill-bar html">
                                <span class="skill-per">
                                    <span class="tooltip">90%</span>
                                </span>
                            </div>
                        </div>
                        <div class="skill-box">
                            <span class="title">CSS</span>
                            <div class="skill-bar css">
                                <span class="skill-per">
                                    <span class="tooltip">80%</span>
                                </span>
                            </div>
                        </div>
                        <div class="skill-box">
                            <span class="title">JAVASCRIPT</span>
                            <div class="skill-bar">
                                <span class="skill-per javascript">
                                    <span class="tooltip">30%</span>
                                </span>
                            </div>
                        </div>
                        <div class="skill-box">
                            <span class="title">PHP</span>
                            <div class="skill-bar">
                                <span class="skill-per php">
                                    <span class="tooltip">60%</span>
                                </span>
                            </div>
                        </div>
                    
                    </div>
                </div>
             </section>

             <!-- projects sec -->
              <section class="section" id="projects">
                <div class="top-header">
                    <h1>Projects</h1>
                </div>
                <div class="project-container">
                    <div class="project-box">
                        <i class="uil uil-briefcase-alt"></i>
                        <h3>Completed</h3>
                        <label>15+ Finished Projects</label>
                    </div>
                    <div class="project-box">
                        <i class="uil uil-users-alt"></i>
                        <h3>Clients</h3>
                        <label>0 Clients </label>
                    </div>
                    <div class="project-box">
                        <i class="uil uil-award"></i>
                        <h3>experience</h3>
                        <label>1+ Years in web development</label>
                    </div>
                </div>
              </section>

              <!-- contact  section-->
               <section class="section" id="contact">
                <div class="top-header">
                    <h1>Let's Work Together</h1>
                    <span>Do you have a Project in your mind, contact me her</span>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="contact-info">
                            <h2>Find Me <i class="uil uil-corner-right-down"></i></h2>
                            <p><i class="uil uil-envelope"></i>Email:eburna69@gmail.com</p>
                            <p><i class="uil uil-phone"></i>Phone:0709135793</p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-control">
                            <div class="form-inputs">
                                <input type="text" class="input-field" placeholder="Your Name" required>
                                <input type="email" class="input-field" placeholder="Your Email" required>
                            </div>
                            <div class="text-area">
                                <input type="text" class="input-subject" placeholder="Subject">
                                <textarea placeholder="Message"></textarea>
                            </div>
                            <div class="form-button">
                                <button class="btn">Send <i class="uil uil-message"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
               </section>
               <!-- footer section -->
                <footer>
                    <div class="middle-footer">
                        <ul class="footer-menu">
                            <li class="footer_menu_list">
                                <a href="#home">Home</a>
                            </li>
                            <li class="footer_menu_list">
                                <a href="#about">About</a>
                            </li>
                            <li class="footer_menu_list">
                                <a href="#project">Projects</a>
                            </li>
                            <li class="footer_menu_list">
                                <a href="#contact">Contact</a>
                            </li>
                        </ul>
                    </div>
                    <div class="footer-social-icons">
                        <div class="icon"><a href="https://instagram.com/call_mi_burnaclout" target="_blank"><i class="uil uil-instagram"></i></a></div>
                        <div class="icon"><a href="https://facebook.com/burnaclout" target="_blank"><i class="uil uil-facebook"></i></a></div>
                        <div class="icon"><a href="https://linkedin.com/in/burna-clout-52474930" target="_blank"><i class="uil uil-linkedin-alt"></i></a></div>
                        <div class="icon"><a href="https://x.com/burnaclout616" target="_blank"><i class="uil uil-twitter-alt"></i></a></div>
                        <div class="icon"><a href="https://github.com/AndriodBurna" target="_blank"><i class="uil uil-github-alt"></i></a></div>
                    </div>
                    <div class="bottom-footer">
                        <p>Copyright &copy;<a href="./index.php" style="text-decoration: none;">Burnaclout</a> - All rights reserved 2024</p>
                    </div>
                </footer>
         </main>
     </div>

     <script src="https://cdnjs.cloudflare.com/ajax/libs/typed.js/2.1.0/typed.umd.js"></script>
     <script src="https://cdnjs.cloudflare.com/ajax/libs/scrollReveal.js/4.0.9/scrollreveal.min.js"></script>
     <script src="./assets/js/portifolio.js"></script>
</body>
</html>