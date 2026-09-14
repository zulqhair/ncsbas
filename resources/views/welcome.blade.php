@extends('layouts.app')
@section('title', 'National Cyber Security Baseline Assessment')
@section('content')
    <section class="hero-panel hero-layout" aria-labelledby="welcome-heading">
        <div>
            <p class="eyebrow">NCSB v1.1 · Assessment platform</p>
            <h1 id="welcome-heading">A clear view of your cyber security baseline.</h1>
            <p class="hero-description">Assess your practices, work with a reviewer, and turn your NCSB responses into a structured maturity report.</p>
            <div class="hero-actions">
                <a class="btn btn-signal" href="{{ route('register') }}">Register as Assessor <x-icon name="arrow" /></a>
                <a class="btn btn-outline-light" href="{{ route('login') }}">Log in to your workspace</a>
            </div>
        </div>
        <div class="baseline-diagram">
            <div class="diagram-header"><strong>Your assessment framework</strong><x-icon name="shield" size="32" /></div>
            <ol class="domain-list">
                <li><span>01</span> Govern</li><li><span>02</span> Identify</li>
                <li><span>03</span> Protect</li><li><span>04</span> Detect</li>
                <li><span>05</span> Respond</li><li><span>06</span> Recover</li>
            </ol>
            <p class="diagram-note">Six domains. 33 baseline elements.<br>One assessment and review workflow.</p>
        </div>
    </section>
    <div class="notice baseline-notice"><x-icon name="info" /><div><strong>Start with a draft. Submit when you’re ready.</strong><p class="mb-0 text-secondary">Save your responses as you go. Submitting for review locks the assessment for editing.</p></div></div>
    <section id="assessment-process" aria-labelledby="process-heading">
        <div class="section-heading"><div><p class="eyebrow">From assessment to report</p><h2 id="process-heading" class="mb-0">A structured process, at every stage.</h2></div></div>
        <ol class="process-list">
            <li><span class="process-number">01 / Assess</span><h3>Establish your baseline</h3><p>Answer each element in sequence. Save your draft and calculate the maturity scores from your responses.</p></li>
            <li><span class="process-number">02 / Review</span><h3>Bring in a reviewer</h3><p>Submit your assessment to a reviewer, exchange comments, and follow the review through to completion.</p></li>
            <li><span class="process-number">03 / Report</span><h3>Keep a clear record</h3><p>View overall and element-level results in your workspace, and download a PDF assessment report.</p></li>
        </ol>
    </section>
    <section id="workspace-roles" class="roles-section" aria-labelledby="roles-heading">
        <div><p class="eyebrow">Access by responsibility</p><h2 id="roles-heading">The right workspace for your role.</h2><p class="text-secondary">New accounts start as Assessors. Administrators manage Reviewer and Admin access.</p></div>
        <dl class="role-list">
            <div><dt><x-icon name="document" /> Assessor</dt><dd>Create and complete assessments, request reviews, and access your results.</dd></div>
            <div><dt><x-icon name="review" /> Reviewer</dt><dd>Manage your assignments, examine responses, and record review feedback.</dd></div>
            <div><dt><x-icon name="users" /> Administrator</dt><dd>Oversee assessments and reviews, assign reviewers, and manage user roles.</dd></div>
        </dl>
    </section>
@endsection
