@props(['review', 'canComment' => true])
<ul class="comments-list">
    @forelse($review->comments as $comment)
        <li class="comment"><div class="comment-header"><strong>{{ $comment->user->name }}</strong><time datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->format('d M Y, H:i') }}</time></div><p>{{ $comment->body }}</p></li>
    @empty
        <li class="text-secondary">No comments yet.</li>
    @endforelse
</ul>
@if($canComment)
    <form class="comment-form mt-4" method="POST" action="{{ route('reviews.comments', $review) }}" data-loading-form>
        @csrf
        <label class="form-label" for="comment-{{ $review->id }}">Add a review comment</label>
        <textarea class="form-control mb-3" id="comment-{{ $review->id }}" name="body" rows="3" maxlength="5000" required placeholder="Share feedback or ask for clarification."></textarea>
        <button class="btn btn-outline-primary" type="submit" data-loading-label="Adding comment…">Add comment <x-icon name="arrow" /></button>
    </form>
@endif
