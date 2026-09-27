<style>
    .candidate-card{transition:all .3s cubic-bezier(.4,0,.2,1)}
    .candidate-card.selected{border-color:#4f46e5;background:linear-gradient(135deg,#eef2ff,#e0e7ff);box-shadow:0 0 0 3px rgba(79,70,229,.2),0 10px 25px -5px rgba(79,70,229,.15);transform:translateY(-2px)}
    .candidate-card:hover:not(.selected){border-color:#a5b4fc;box-shadow:0 10px 25px -5px rgba(0,0,0,.1);transform:translateY(-2px)}
    .candidate-card:focus-within{outline:2px solid #4f46e5;outline-offset:2px}
    .check-badge{opacity:0;transform:scale(.5);transition:all .3s cubic-bezier(.4,0,.2,1)}
    .candidate-card.selected .check-badge{opacity:1;transform:scale(1)}
    .photo-wrapper{overflow:hidden}
    .candidate-card.selected .photo-wrapper img{transform:scale(1.05)}
    .photo-wrapper img{transition:transform .3s ease}
</style>
