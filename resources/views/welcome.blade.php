@extends('layouts.app')
@section('title', 'National Cyber Security Baseline Assessment')
@section('content')
    <section class="hero-panel hero-layout" aria-labelledby="welcome-heading">
        <div>
            <p class="eyebrow">NCSB v1.1 · Assessment platform</p>
            <h1 id="welcome-heading">Complete your NCSB self-assessment with a clearer workflow.</h1>
            <p class="hero-description">NCSBAS digitises the NCSB v1.1 Excel self-assessment for authorised users. Work through the questions, understand your maturity results, and keep a structured record for review.</p>
            <div class="hero-actions">
                <a class="btn btn-signal" href="{{ route('register') }}">Register as Assessor <x-icon name="arrow" /></a>
                <a class="btn btn-outline-light" href="{{ route('login') }}">Log in to your workspace</a>
            </div>
        </div>
        <div class="baseline-diagram">
            <div class="diagram-header"><strong>NCSB v1.1 at a glance</strong><x-icon name="shield" size="32" /></div>
            <ol class="domain-list">
                <li><span>01</span> Govern</li><li><span>02</span> Identify</li>
                <li><span>03</span> Protect</li><li><span>04</span> Detect</li>
                <li><span>05</span> Respond</li><li><span>06</span> Recover</li>
            </ol>
            <p class="diagram-note">Six domains. 33 baseline elements.<br>125 questions. Four maturity levels.</p>
        </div>
    </section>
    <div class="notice baseline-notice"><x-icon name="info" /><div><strong>Assessment content follows the NCSB v1.1 template.</strong><p class="mb-0 text-secondary">Save a draft as you go. Submitting for review locks the assessment for editing. NCSBAS is a digital workflow tool, not the official NACSA portal; refer to <a href="https://www.nacsa.gov.my/legal.php" rel="external">NACSA’s official resources</a> for the current baseline and related directives.</p></div></div>
    <section id="assessment-process" aria-labelledby="process-heading">
        <div class="section-heading"><div><p class="eyebrow">From Excel template to workspace</p><h2 id="process-heading" class="mb-0">A structured process at every stage.</h2></div></div>
        <ol class="process-list">
            <li><span class="process-number">01 / Assess</span><h3>Work through each element</h3><p>Answer the NCSB questions in sequence, save a draft as you go, and keep your responses in one controlled assessment record.</p></li>
            <li><span class="process-number">02 / Understand results</span><h3>See your maturity baseline</h3><p>Use the calculated element-level and overall maturity results to identify the areas that need attention.</p></li>
            <li><span class="process-number">03 / Review and report</span><h3>Share a review-ready record</h3><p>Submit your completed assessment for review, exchange comments, and download a PDF report for your records.</p></li>
        </ol>
    </section>
    <section class="platform-explainer" aria-labelledby="platform-heading">
        <div>
            <p class="eyebrow">Why use NCSBAS?</p>
            <h2 id="platform-heading">The NCSB assessment, without manual workbook handling.</h2>
            <p class="text-secondary mb-0">The source assessment remains the NCSB v1.1 template. NCSBAS gives users a clearer way to complete, review, and retain the assessment process.</p>
        </div>
        <dl class="benefit-list">
            <div><dt>Guided questionnaire</dt><dd>Follow the required question sequence for each of the 33 baseline elements.</dd></div>
            <div><dt>Calculated maturity results</dt><dd>View individual element results and the overall maturity level from the assessment responses.</dd></div>
            <div><dt>Controlled review workflow</dt><dd>Save drafts, submit a completed assessment to a reviewer, and retain comments and the final report together.</dd></div>
        </dl>
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
