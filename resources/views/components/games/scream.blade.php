<div>
    <form method="POST"
          action="{{route('submit-scream-game-result')}}"
          enctype="multipart/form-data"
    >
        @csrf

        <label>
            Upload a video of you screaming
            <input type="file"
                   name="scream_file"
                   value="{{old('scream_file')}}"
                   required
                   accept="video/*"
                   @error('scream_file') aria-invalid="true"
                   @enderror
                   aria-describedby="scream-file-helper-text"
            />

            @error('scream_file')
            <small id="scream-file-helper-text">{{$message}}</small>
            @enderror
        </label>

        <button type="submit">Rate my scream</button>
    </form>
</div>
