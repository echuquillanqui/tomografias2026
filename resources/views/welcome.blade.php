<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Portal clínico para la gestión segura de estudios de tomografía computarizada.">
    <title>{{ config('app.name', 'Portal Tomografía') }} | Diagnóstico por imágenes</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet">
    <style>
        :root{--navy:#061c33;--blue:#0a3154;--teal:#18b6a4;--mint:#74eadc;--white:#fff;--muted:#a9bdca;--ink:#102d43}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--navy);color:var(--white);font-family:Manrope,sans-serif}a{color:inherit;text-decoration:none}button,a{font:inherit}.shell{width:min(1180px,calc(100% - 48px));margin:auto}
        .hero{min-height:760px;position:relative;overflow:hidden;background:linear-gradient(90deg,rgba(4,22,40,.98) 0%,rgba(4,25,44,.94) 43%,rgba(4,25,44,.44) 72%,rgba(4,25,44,.2) 100%),url('https://images.pexels.com/photos/7659564/pexels-photo-7659564.jpeg?auto=compress&cs=tinysrgb&w=2000') center right/cover no-repeat}
        .hero:after{content:"";position:absolute;inset:auto 0 0;height:220px;background:linear-gradient(transparent,var(--navy));pointer-events:none}.glow{position:absolute;width:460px;height:460px;left:-230px;top:170px;border-radius:50%;background:rgba(24,182,164,.14);filter:blur(35px)}
        nav{height:92px;display:flex;align-items:center;justify-content:space-between;position:relative;z-index:2;border-bottom:1px solid rgba(255,255,255,.1)}.brand{display:flex;align-items:center;gap:12px;font-weight:800;letter-spacing:-.04em;font-size:19px}.brand-mark{width:43px;height:43px;display:grid;place-items:center;border-radius:14px;background:linear-gradient(135deg,var(--teal),#0c6781);box-shadow:0 8px 28px rgba(24,182,164,.3)}.brand-mark svg{width:25px}.nav-actions{display:flex;align-items:center;gap:12px}.btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:13px 21px;border-radius:999px;font-weight:700;font-size:14px;transition:.25s ease}.btn:hover{transform:translateY(-2px)}.btn-ghost{border:1px solid rgba(255,255,255,.26);background:rgba(255,255,255,.06);backdrop-filter:blur(12px)}.btn-primary{background:var(--teal);color:#041e2d;box-shadow:0 12px 35px rgba(24,182,164,.28)}
        .hero-content{position:relative;z-index:1;padding:104px 0 180px;max-width:690px}.eyebrow{display:inline-flex;align-items:center;gap:10px;color:var(--mint);font-size:12px;font-weight:800;letter-spacing:.16em;text-transform:uppercase}.eyebrow:before{content:"";width:28px;height:2px;background:var(--teal)}h1{font-size:clamp(50px,6.1vw,78px);line-height:1.03;letter-spacing:-.065em;margin:20px 0 25px;max-width:750px}.accent{color:var(--mint)}.lead{max-width:620px;color:#cadae3;font-size:18px;line-height:1.75;margin:0 0 37px}.hero-actions{display:flex;gap:14px;flex-wrap:wrap}.hero-actions .btn{padding:16px 25px}.trust{display:flex;align-items:center;gap:18px;margin-top:47px;color:#b9cbd6;font-size:13px}.avatars{display:flex}.avatar{width:35px;height:35px;margin-left:-8px;border:2px solid #08253f;border-radius:50%;display:grid;place-items:center;background:#d9eef0;color:#0b5360;font-weight:800;font-size:11px}.avatar:first-child{margin:0}.status-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#4ee0a0;box-shadow:0 0 0 5px rgba(78,224,160,.12);margin-right:8px}
        .dashboard-card{position:absolute;z-index:2;right:max(6vw,25px);bottom:95px;width:275px;padding:20px;border:1px solid rgba(255,255,255,.17);border-radius:22px;background:rgba(6,28,51,.78);box-shadow:0 25px 60px rgba(0,0,0,.3);backdrop-filter:blur(18px)}.scan-top{display:flex;justify-content:space-between;align-items:center}.scan-top small{color:var(--muted);font-size:10px;letter-spacing:.12em;text-transform:uppercase}.scan-top strong{font-size:13px}.scan-icon{margin:17px 0;height:94px;border-radius:16px;background:radial-gradient(circle,transparent 18%,rgba(116,234,220,.55) 19%,rgba(116,234,220,.05) 21%,transparent 34%),linear-gradient(135deg,rgba(24,182,164,.25),rgba(37,84,116,.15));position:relative;overflow:hidden}.scan-icon:after{content:"";position:absolute;left:0;right:0;top:49%;height:1px;background:var(--mint);box-shadow:0 0 12px var(--teal)}.progress{height:5px;background:rgba(255,255,255,.1);border-radius:99px;overflow:hidden}.progress span{display:block;width:84%;height:100%;background:linear-gradient(90deg,var(--teal),var(--mint))}.scan-foot{display:flex;justify-content:space-between;margin-top:12px;color:var(--muted);font-size:11px}.scan-foot b{color:var(--mint)}
        .features{position:relative;z-index:3;margin-top:-52px;padding-bottom:100px}.feature-grid{display:grid;grid-template-columns:repeat(3,1fr);border:1px solid rgba(255,255,255,.1);border-radius:26px;background:#09243e;box-shadow:0 30px 70px rgba(0,0,0,.18);overflow:hidden}.feature{padding:34px;display:flex;gap:20px}.feature+.feature{border-left:1px solid rgba(255,255,255,.1)}.feature-icon{flex:0 0 48px;height:48px;border-radius:15px;display:grid;place-items:center;background:rgba(24,182,164,.12);color:var(--mint)}.feature-icon svg{width:24px}.feature h2{font-size:16px;margin:2px 0 8px}.feature p{color:#8faabc;font-size:13px;line-height:1.65;margin:0}
        .services{background:#f5fafb;color:var(--ink);padding:105px 0}.section-head{display:flex;align-items:end;justify-content:space-between;gap:30px;margin-bottom:45px}.section-head h2{font-size:clamp(34px,4vw,50px);letter-spacing:-.05em;line-height:1.08;margin:13px 0 0}.section-head p{max-width:450px;color:#648092;line-height:1.7;margin:0}.section-head .eyebrow{color:#0c8d80}.service-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}.service{padding:30px;background:white;border:1px solid #e1ecef;border-radius:22px;transition:.25s}.service:hover{transform:translateY(-6px);box-shadow:0 20px 50px rgba(17,63,76,.1);border-color:#b9e4df}.number{color:#0b9e90;font-size:12px;font-weight:800;letter-spacing:.15em}.service h3{font-size:21px;margin:29px 0 11px}.service p{color:#668092;font-size:14px;line-height:1.7;margin:0}.service-arrow{display:grid;place-items:center;margin-top:26px;width:38px;height:38px;border-radius:50%;background:#ecf8f6;color:#0a8e82}
        footer{background:#f5fafb;color:#718796;padding:0 0 30px}.footer-inner{padding-top:25px;border-top:1px solid #dce8eb;display:flex;justify-content:space-between;font-size:12px}.photo-credit a{text-decoration:underline}
        @media(max-width:900px){.dashboard-card{display:none}.hero{background-position:62% center}.hero-content{padding-top:85px}.feature-grid,.service-grid{grid-template-columns:1fr}.feature+.feature{border-left:0;border-top:1px solid rgba(255,255,255,.1)}.section-head{align-items:start;flex-direction:column}.services{padding:80px 0}}
        @media(max-width:600px){.shell{width:min(100% - 30px,1180px)}nav{height:76px}.brand span{display:none}.btn-ghost{display:none}.hero{min-height:710px;background:linear-gradient(rgba(4,22,40,.9),rgba(4,22,40,.97)),url('https://images.pexels.com/photos/7659564/pexels-photo-7659564.jpeg?auto=compress&cs=tinysrgb&w=900') center/cover}.hero-content{padding:83px 0 130px}h1{font-size:45px}.lead{font-size:16px}.hero-actions{display:grid}.hero-actions .btn{width:100%}.trust{align-items:flex-start;flex-direction:column;gap:12px}.features{margin-top:-35px}.feature{padding:27px 24px}.footer-inner{gap:12px;flex-direction:column}}
    </style>
</head>
<body>
<header class="hero">
    <div class="glow"></div>
    <div class="shell">
        <nav aria-label="Navegación principal">
            <a class="brand" href="{{ url('/') }}" aria-label="Inicio">
                <span class="brand-mark"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3v18M17 3v18M3 7h18M3 17h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="12" r="3" fill="currentColor"/></svg></span>
                <span>{{ config('app.name', 'Portal Tomografía') }}</span>
            </a>
            <div class="nav-actions">
                @auth
                    <a class="btn btn-primary" href="{{ url('/home') }}">Ir al panel <span aria-hidden="true">→</span></a>
                @else
                    <a class="btn btn-ghost" href="#servicios">Conocer el sistema</a>
                    <a class="btn btn-primary" href="{{ route('login') }}">Acceso profesional <span aria-hidden="true">→</span></a>
                @endauth
            </div>
        </nav>
        <main class="hero-content">
            <span class="eyebrow">Tecnología que cuida</span>
            <h1>Precisión que se ve.<br><span class="accent">Confianza que se siente.</span></h1>
            <p class="lead">Una plataforma integral para gestionar estudios de tomografía, pacientes e informes con rapidez, seguridad y la precisión que cada diagnóstico merece.</p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="{{ auth()->check() ? url('/home') : route('login') }}">Ingresar al portal <span aria-hidden="true">→</span></a>
                <a class="btn btn-ghost" href="#servicios"><svg width="17" viewBox="0 0 24 24" fill="none"><path d="M8 5v14l11-7L8 5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg> Explorar plataforma</a>
            </div>
            <div class="trust">
                <div class="avatars" aria-hidden="true"><span class="avatar">RX</span><span class="avatar">TC</span><span class="avatar">MD</span></div>
                <span><i class="status-dot"></i>Sistema operativo y protegido para el equipo clínico</span>
            </div>
        </main>
    </div>
    <aside class="dashboard-card" aria-label="Estado del sistema">
        <div class="scan-top"><div><small>Procesamiento</small><br><strong>Estudio tomográfico</strong></div><small>En línea</small></div>
        <div class="scan-icon"></div><div class="progress"><span></span></div>
        <div class="scan-foot"><span>Calidad de imagen</span><b>Óptima</b></div>
    </aside>
</header>

<section class="features" aria-label="Beneficios principales">
    <div class="shell feature-grid">
        <article class="feature"><span class="feature-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M12 3 5 6v5c0 4.7 2.9 8.3 7 10 4.1-1.7 7-5.3 7-10V6l-7-3Z" stroke="currentColor" stroke-width="1.8"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.8"/></svg></span><div><h2>Información protegida</h2><p>Acceso seguro para personal médico y administrativo autorizado.</p></div></article>
        <article class="feature"><span class="feature-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16v14H4zM8 9h8M8 13h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><div><h2>Gestión centralizada</h2><p>Pacientes, órdenes e informes organizados en un solo lugar.</p></div></article>
        <article class="feature"><span class="feature-icon"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8"/><path d="M12 8v4l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><div><h2>Atención más ágil</h2><p>Flujos clínicos eficientes para acompañar cada estudio a tiempo.</p></div></article>
    </div>
</section>

<section class="services" id="servicios">
    <div class="shell">
        <div class="section-head"><div><span class="eyebrow">Portal clínico</span><h2>Todo el proceso,<br>en perfecta sincronía.</h2></div><p>Herramientas diseñadas para que el equipo se concentre en lo más importante: brindar una atención clara, ágil y confiable.</p></div>
        <div class="service-grid">
            <article class="service"><span class="number">01 / PACIENTES</span><h3>Admisión inteligente</h3><p>Registra datos, antecedentes y documentación clínica con un flujo simple y ordenado.</p><span class="service-arrow">→</span></article>
            <article class="service"><span class="number">02 / ESTUDIOS</span><h3>Control de tomografías</h3><p>Da seguimiento a cada orden, examen y estado del estudio desde un panel centralizado.</p><span class="service-arrow">→</span></article>
            <article class="service"><span class="number">03 / RESULTADOS</span><h3>Informes precisos</h3><p>Gestiona reportes radiológicos y facilita una entrega segura de resultados al paciente.</p><span class="service-arrow">→</span></article>
        </div>
    </div>
</section>
<footer><div class="shell footer-inner"><span>© {{ date('Y') }} {{ config('app.name', 'Portal Tomografía') }}. Uso profesional autorizado.</span><span class="photo-credit">Fotografía médica: <a href="https://www.pexels.com/photo/7659564/" target="_blank" rel="noopener">Pexels</a></span></div></footer>
</body>
</html>
